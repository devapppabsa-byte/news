<?php

namespace App\Actions;

use App\DTO\SearchResult;
use App\DTO\WebSearchQuery;
use App\Enums\RunStatus;
use App\Enums\RunTrigger;
use App\Models\MonitoredTopic;
use App\Models\TopicRun;
use App\Services\AI\AiAnalysisService;
use App\Services\Novedades\NovedadPipeline;
use App\Services\Search\ProviderRegistry;
use App\Services\Settings\SettingsRepository;
use App\Support\CostEstimator;
use App\Support\SettingKeys;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RunMonitoredTopicSearch
{
    public function __construct(
        protected ProviderRegistry $registry,
        protected AiAnalysisService $analysis,
        protected NovedadPipeline $novedades,
        protected CostEstimator $costs,
        protected SettingsRepository $settings,
    ) {}

    /**
     * Execute a search across every enabled provider, persist the results and
     * refresh the topic schedule. Accepts an existing queued run, or creates a
     * fresh one when invoked directly.
     */
    public function execute(MonitoredTopic $topic, RunTrigger $trigger, ?TopicRun $run = null): TopicRun
    {
        $run ??= $topic->runs()->create([
            'trigger' => $trigger,
            'status' => RunStatus::Queued,
            'queued_at' => now(),
        ]);

        $startedAt = now();

        $run->update([
            'status' => RunStatus::Running,
            'started_at' => $startedAt,
            'attempts' => $run->attempts + 1,
        ]);

        try {
            $outcome = $this->registry->search($this->buildQuery($topic));
            $results = $outcome['results'];

            $summary = $this->novedades->process($topic, $run, $results);
            $stored = $summary['stored'];

            $aiUsage = $this->emptyAiUsage();

            if ($stored > 0 && (bool) config('ai.analyze_after_search', true)) {
                $aiUsage = $this->enrichWithAi($run);
            }

            $providerUsage = $this->providerUsage($results, $outcome['errors'], $aiUsage);

            $run->update([
                'status' => RunStatus::Success,
                'results_count' => $stored,
                'error_message' => $outcome['errors'] === [] ? null : implode('; ', $outcome['errors']),
                'finished_at' => now(),
                'duration_ms' => $startedAt->diffInMilliseconds(now()),
                'provider_usage' => $providerUsage,
                'estimated_cost' => $this->costs->estimate([
                    'results' => $stored,
                    'ai_tokens' => (int) $providerUsage['ai_tokens'],
                ]),
            ]);

            $this->advanceSchedule($topic);
        } catch (\Throwable $exception) {
            $run->update([
                'status' => RunStatus::Failed,
                'results_count' => 0,
                'error_message' => $this->sanitize($exception->getMessage()),
                'finished_at' => now(),
                'duration_ms' => $startedAt->diffInMilliseconds(now()),
            ]);
        }

        return $run;
    }

    /**
     * Advance last_run_at and the next schedule. Only called on a successful
     * run so that transient failures are retried instead of skipping a cycle.
     */
    protected function advanceSchedule(MonitoredTopic $topic): void
    {
        $topic->forceFill(['last_run_at' => now()]);
        $topic->scheduleNextRun();
        $topic->save();
    }

    protected function buildQuery(MonitoredTopic $topic): WebSearchQuery
    {
        $topic->loadMissing(['keywords', 'sources']);

        return new WebSearchQuery(
            term: $topic->name,
            includeKeywords: $topic->includedKeywords->pluck('value')->all(),
            excludeKeywords: $topic->excludedKeywords->pluck('value')->all(),
            preferredDomains: $topic->preferredSources->pluck('domain')->all(),
            excludedDomains: $topic->excludedSources->pluck('domain')->all(),
            language: $topic->language,
            country: $topic->country,
            limit: $this->settings->integer(SettingKeys::SEARCH_MAX_RESULTS),
            timeout: $this->settings->integer(SettingKeys::SEARCH_TIMEOUT),
            retries: $this->settings->integer(SettingKeys::SEARCH_RETRIES),
        );
    }

    /**
     * @param  array<int, SearchResult>  $results
     * @param  array<int, string>  $errors
     * @param  array{ai_tokens?: int, ai_provider?: ?string, ai_model?: ?string}  $aiUsage
     * @return array<string, mixed>
     */
    protected function providerUsage(array $results, array $errors, array $aiUsage = []): array
    {
        $byProvider = [];

        foreach ($results as $result) {
            $key = $result->sourceIdentifier ?? 'unknown';
            $byProvider[$key] = ($byProvider[$key] ?? 0) + 1;
        }

        return [
            'results_by_provider' => $byProvider,
            'errors' => count($errors),
            'ai_tokens' => (int) ($aiUsage['ai_tokens'] ?? 0),
            'ai_provider' => $aiUsage['ai_provider'] ?? null,
            'ai_model' => $aiUsage['ai_model'] ?? null,
        ];
    }

    /**
     * Enrich the persisted items with AI metadata. Failures never fail the run.
     *
     * @return array{ai_tokens: int, ai_provider: ?string, ai_model: ?string}
     */
    protected function enrichWithAi(TopicRun $run): array
    {
        try {
            return $this->analysis->analyzeRun($run);
        } catch (\Throwable $exception) {
            Log::warning('AI enrichment failed for topic run', [
                'topic_run_id' => $run->id,
                'message' => $this->sanitize($exception->getMessage()),
            ]);

            return $this->emptyAiUsage();
        }
    }

    /**
     * @return array{ai_tokens: int, ai_provider: ?string, ai_model: ?string}
     */
    protected function emptyAiUsage(): array
    {
        return ['ai_tokens' => 0, 'ai_provider' => null, 'ai_model' => null];
    }

    protected function sanitize(string $message): string
    {
        return Str::limit(preg_replace('/sk-[A-Za-z0-9\-_]+/', '[redacted]', $message) ?? $message, 500);
    }
}
