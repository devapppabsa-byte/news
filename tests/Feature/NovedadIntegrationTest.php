<?php

use App\Actions\RunMonitoredTopicSearch;
use App\DTO\SearchResult;
use App\Enums\NewsItemStatus;
use App\Enums\RunStatus;
use App\Enums\RunTrigger;
use App\Models\MonitoredTopic;
use App\Models\NewsEntry;
use App\Models\NewsItem;
use App\Models\User;
use App\Services\AI\AiAnalysisService;
use App\Services\Search\ProviderRegistry;

test('a full run stores global entries with novedad statuses', function () {
    $registry = Mockery::mock(ProviderRegistry::class);
    $this->instance(ProviderRegistry::class, $registry);

    $registry->shouldReceive('search')->andReturn([
        'results' => [
            new SearchResult('Fresh one', 'https://a.com/1', 'a.com', 'body one'),
            new SearchResult('Fresh two', 'https://b.com/2', 'b.com', 'body two'),
        ],
        'errors' => [],
    ]);

    $analysis = Mockery::mock(AiAnalysisService::class);
    $analysis->shouldReceive('assessNovedad')->andReturn([
        'relevance' => 80, 'summary' => 'S', 'is_irrelevant' => false, 'reason' => null,
    ]);
    $analysis->shouldReceive('analyzeRun')->andReturn(['analyzed' => 0, 'ai_tokens' => 0, 'ai_provider' => null, 'ai_model' => null]);
    $this->instance(AiAnalysisService::class, $analysis);

    $topic = MonitoredTopic::factory()->for(User::factory())->create();

    $run = app(RunMonitoredTopicSearch::class)->execute($topic, RunTrigger::Manual);

    expect($run->status)->toBe(RunStatus::Success)
        ->and($run->results_count)->toBe(2)
        ->and(NewsEntry::query()->count())->toBe(2)
        ->and(NewsItem::query()->where('topic_id', $topic->id)->count())->toBe(2)
        ->and(NewsItem::query()->where('novedad_status', NewsItemStatus::New)->count())->toBe(2);
});

test('re-running the same search keeps one entry and marks items existing', function () {
    $registry = Mockery::mock(ProviderRegistry::class);
    $this->instance(ProviderRegistry::class, $registry);

    $registry->shouldReceive('search')->andReturn([
        'results' => [new SearchResult('Story', 'https://a.com/1', 'a.com', 'body')],
        'errors' => [],
    ]);

    $analysis = Mockery::mock(AiAnalysisService::class);
    $analysis->shouldReceive('assessNovedad')->andReturn([
        'relevance' => 80, 'summary' => 'S', 'is_irrelevant' => false, 'reason' => null,
    ]);
    $analysis->shouldReceive('analyzeRun')->andReturn(['analyzed' => 0, 'ai_tokens' => 0, 'ai_provider' => null, 'ai_model' => null]);
    $this->instance(AiAnalysisService::class, $analysis);

    $topic = MonitoredTopic::factory()->for(User::factory())->create();
    $action = app(RunMonitoredTopicSearch::class);

    $action->execute($topic, RunTrigger::Manual);
    $action->execute($topic, RunTrigger::Manual);

    expect(NewsEntry::query()->count())->toBe(1)
        ->and(NewsItem::query()->where('topic_id', $topic->id)->count())->toBe(1);
});
