<?php

namespace App\Services\Novedades;

use App\DTO\SearchResult;
use App\Enums\NewsItemStatus;
use App\Models\MonitoredTopic;
use App\Models\NewsEntry;
use App\Models\NewsItem;
use App\Models\TopicRun;
use App\Services\AI\AiAnalysisService;
use App\Services\Notifications\NovedadNotifier;
use App\Support\UrlNormalizer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NovedadPipeline
{
    public function __construct(
        protected NewsEntryRepository $entries,
        protected SourceAvailabilityChecker $sources,
        protected AiAnalysisService $analysis,
        protected UrlNormalizer $normalizer,
        protected NovedadNotifier $notifier,
    ) {}

    /**
     * Turn raw search results into globally deduplicated news entries linked to
     * the topic. Returns the outcome counts for the run.
     *
     * @param  array<int, SearchResult>  $results
     * @return array{new: int, existing: int, irrelevant: int, stored: int}
     */
    public function process(MonitoredTopic $topic, TopicRun $run, array $results): array
    {
        $summary = ['new' => 0, 'existing' => 0, 'irrelevant' => 0, 'stored' => 0];
        $analyze = (bool) config('ai.analyze_after_search', true);

        foreach ($results as $result) {
            $entry = $this->entries->findExisting($result->url, $result->title, $result->snippet);
            $isNewForSystem = $entry === null;

            if ($entry === null) {
                $entry = $this->createEntry($result);
            }

            $existingLink = $this->existingLink($topic, $entry);

            if ($existingLink !== null) {
                $existingLink->forceFill(['novedad_status' => NewsItemStatus::Existing])->save();
                $summary['existing']++;

                continue;
            }

            $status = $isNewForSystem ? NewsItemStatus::New : NewsItemStatus::Existing;

            if ($analyze) {
                $status = $this->assess($entry, $status);
            }

            $item = $this->link($topic, $run, $entry, $status);

            if ($status === NewsItemStatus::New) {
                $this->notifier->dispatchIndividualAlert($item);
                $this->notifier->dispatchPushAlert($item);
            }

            match ($status) {
                NewsItemStatus::Irrelevant => $summary['irrelevant']++,
                NewsItemStatus::Existing => $summary['existing']++,
                NewsItemStatus::New => $summary['new']++,
            };

            $summary['stored']++;
        }

        return $summary;
    }

    protected function createEntry(SearchResult $result): NewsEntry
    {
        $availability = $this->sources->check($result->url);

        return $this->entries->create($result->url, [
            'title' => Str::limit($result->title, 250, ''),
            'content_hash' => $this->entries->contentHash($result->title, $result->snippet),
            'source_name' => $result->domain,
            'source_domain' => $result->domain,
            'published_at' => $result->publishedAt,
            'discovered_at' => $result->collectedAt ?? now(),
            'summary' => $result->snippet,
            'source_available' => $availability['available'],
            'source_checked_at' => $availability['checked_at'],
            'status' => NewsItemStatus::New,
        ]);
    }

    /**
     * Run AI assessment and persist the resulting score/summary. A failure is
     * non fatal: the entry keeps its current status.
     */
    protected function assess(NewsEntry $entry, NewsItemStatus $fallback): NewsItemStatus
    {
        try {
            $assessment = $this->analysis->assessNovedad($entry);
        } catch (\Throwable $exception) {
            Log::warning('Novedad assessment failed', [
                'news_entry_id' => $entry->id,
                'message' => Str::limit($exception->getMessage(), 300),
            ]);

            return $fallback;
        }

        $minRelevance = (int) config('novedades.min_relevance', 30);
        $isIrrelevant = $assessment['is_irrelevant'] || $assessment['relevance'] < $minRelevance;

        $entry->forceFill([
            'relevance_score' => $assessment['relevance'],
            'summary' => $assessment['summary'] ?? $entry->summary,
            'status' => $isIrrelevant ? NewsItemStatus::Irrelevant : $fallback,
        ])->save();

        return $isIrrelevant ? NewsItemStatus::Irrelevant : $fallback;
    }

    protected function existingLink(MonitoredTopic $topic, NewsEntry $entry): ?NewsItem
    {
        return NewsItem::query()
            ->where('topic_id', $topic->id)
            ->where('news_entry_id', $entry->id)
            ->first();
    }

    protected function link(MonitoredTopic $topic, TopicRun $run, NewsEntry $entry, NewsItemStatus $status): NewsItem
    {
        return NewsItem::query()->create([
            'user_id' => $topic->user_id,
            'topic_id' => $topic->id,
            'topic_run_id' => $run->id,
            'news_entry_id' => $entry->id,
            'title' => $entry->title,
            'slug' => $this->uniqueSlug($entry->title),
            'body' => $entry->summary ?? '',
            'url' => $entry->url,
            'source_domain' => $entry->source_domain,
            'relevance' => $entry->relevance_score ?? 0,
            'collected_at' => $entry->discovered_at,
            'status' => 'published',
            'novedad_status' => $status,
            'published_at' => $entry->published_at,
        ]);
    }

    protected function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'result';
        $slug = $base;
        $suffix = 1;

        while (NewsItem::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
