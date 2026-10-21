<?php

use App\DTO\SearchResult;
use App\Enums\RunStatus;
use App\Enums\RunTrigger;
use App\Jobs\RunMonitoredTopicSearchJob;
use App\Models\MonitoredTopic;
use App\Models\NewsEntry;
use App\Models\TopicRun;
use App\Models\User;
use App\Services\AI\AiAnalysisService;
use App\Services\Search\ProviderRegistry;

function stubSearchRegistry(array $results): void
{
    $registry = Mockery::mock(ProviderRegistry::class);
    $registry->shouldReceive('search')->andReturn(['results' => $results, 'errors' => []]);
    app()->instance(ProviderRegistry::class, $registry);
}

function stubAnalysis(): void
{
    $analysis = Mockery::mock(AiAnalysisService::class);
    $analysis->shouldReceive('assessNovedad')->andReturn([
        'relevance' => 80, 'summary' => 'S', 'is_irrelevant' => false, 'reason' => null,
    ]);
    $analysis->shouldReceive('analyzeRun')->andReturn(['analyzed' => 0, 'ai_tokens' => 0, 'ai_provider' => null, 'ai_model' => null]);
    app()->instance(AiAnalysisService::class, $analysis);
}

test('the job executes the search and records metrics on the run', function () {
    stubSearchRegistry([new SearchResult('Story', 'https://a.com/1', 'a.com', 'body', sourceIdentifier: 'rss')]);
    stubAnalysis();

    $topic = MonitoredTopic::factory()->for(User::factory())->create();

    RunMonitoredTopicSearchJob::dispatchSync($topic->id);

    $run = $topic->runs()->latest('id')->first();

    expect($run->status)->toBe(RunStatus::Success)
        ->and($run->trigger)->toBe(RunTrigger::Scheduled)
        ->and($run->results_count)->toBe(1)
        ->and($run->duration_ms)->not->toBeNull()
        ->and($run->attempts)->toBe(1)
        ->and($run->provider_usage['results_by_provider']['rss'])->toBe(1)
        ->and((float) $run->estimated_cost)->toBe(0.0)
        ->and($topic->fresh()->last_run_at)->not->toBeNull()
        ->and($topic->fresh()->next_run_at)->not->toBeNull();
});

test('the job reuses the queued run instead of creating a new one', function () {
    stubSearchRegistry([]);
    stubAnalysis();

    $topic = MonitoredTopic::factory()->for(User::factory())->create();
    $queued = $topic->runs()->create([
        'trigger' => RunTrigger::Scheduled,
        'status' => RunStatus::Queued,
        'queued_at' => now(),
    ]);

    RunMonitoredTopicSearchJob::dispatchSync($topic->id);

    expect($topic->runs()->count())->toBe(1)
        ->and($queued->fresh()->status)->toBe(RunStatus::Success);
});

test('the job records the provider errors on the run', function () {
    $registry = Mockery::mock(ProviderRegistry::class);
    $registry->shouldReceive('search')->andReturn([
        'results' => [],
        'errors' => ['brave: rate limited'],
    ]);
    app()->instance(ProviderRegistry::class, $registry);
    stubAnalysis();

    $topic = MonitoredTopic::factory()->for(User::factory())->create();

    RunMonitoredTopicSearchJob::dispatchSync($topic->id);

    $run = $topic->runs()->latest('id')->first();

    expect($run->error_message)->toContain('rate limited')
        ->and($run->provider_usage['errors'])->toBe(1);
});

test('the job is unique per topic', function () {
    $job = new RunMonitoredTopicSearchJob(42);

    expect($job->uniqueId())->toBe('monitored-topic-42');
});

test('the job configures retries and a progressive backoff', function () {
    $job = new RunMonitoredTopicSearchJob(1);

    expect($job->tries)->toBe(3)
        ->and($job->backoff)->toBe([60, 300, 900]);
});

test('a missing topic is a no-op', function () {
    RunMonitoredTopicSearchJob::dispatchSync(99999);

    expect(TopicRun::query()->count())->toBe(0)
        ->and(NewsEntry::query()->count())->toBe(0);
});
