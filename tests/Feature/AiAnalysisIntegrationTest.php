<?php

use App\Actions\RunMonitoredTopicSearch;
use App\DTO\SearchResult;
use App\Enums\RunStatus;
use App\Enums\RunTrigger;
use App\Models\MonitoredTopic;
use App\Models\NewsItem;
use App\Models\TopicRun;
use App\Models\User;
use App\Services\AI\AiAnalysisService;
use App\Services\AI\AIConfigRepository;
use App\Services\Search\ProviderRegistry;

function fakeSearchRegistry(array $results): void
{
    $registry = Mockery::mock(ProviderRegistry::class);
    $registry->shouldReceive('search')->andReturn(['results' => $results, 'errors' => []]);
    app()->instance(ProviderRegistry::class, $registry);
}

test('a run enriches its items with ai summary and relevance', function () {
    Http::fake([
        'api.openai.com/*' => Http::sequence()
            ->push(['choices' => [['message' => ['content' => 'A tidy summary.']]]], 200)
            ->push(['choices' => [['message' => ['content' => '{"relevance": 75}']]]], 200)
            ->push(['choices' => [['message' => ['content' => '{"duplicates": []}']]]], 200),
    ]);
    config()->set('ai.providers', ['openai']);
    config()->set('services.openai.key', 'sk-openai-secret');
    config()->set('ai.provider', 'openai');
    config()->set('ai.analyze_after_search', true);
    (new AIConfigRepository)->forget();

    fakeSearchRegistry([
        new SearchResult('Breaking news', 'https://a.com/x', 'a.com', sourceIdentifier: 'rss'),
    ]);

    $user = User::factory()->create();
    $topic = MonitoredTopic::factory()->for($user)->create();

    app(RunMonitoredTopicSearch::class)->execute($topic, RunTrigger::Manual);

    $item = NewsItem::query()->where('topic_id', $topic->id)->first();

    expect($item->ai_summary)->toBe('A tidy summary.')
        ->and($item->ai_relevance)->toBe(75)
        ->and($item->ai_analyzed_at)->not->toBeNull();
});

test('ai failure does not fail the search run', function () {
    Http::fake([
        'api.openai.com/*' => Http::response(['error' => ['message' => 'overloaded']], 503),
    ]);
    config()->set('ai.providers', ['openai']);
    config()->set('services.openai.key', 'sk-openai-secret');
    config()->set('ai.provider', 'openai');
    config()->set('ai.analyze_after_search', true);
    (new AIConfigRepository)->forget();

    fakeSearchRegistry([
        new SearchResult('Breaking news', 'https://a.com/x', 'a.com', sourceIdentifier: 'rss'),
    ]);

    $user = User::factory()->create();
    $topic = MonitoredTopic::factory()->for($user)->create();

    $run = app(RunMonitoredTopicSearch::class)->execute($topic, RunTrigger::Manual);

    expect($run->status)->toBe(RunStatus::Success)
        ->and(NewsItem::query()->where('topic_id', $topic->id)->count())->toBe(1);
});

test('duplicate detection links an item to its original', function () {
    Http::fake([
        'api.openai.com/*' => Http::sequence()
            ->push(['choices' => [['message' => ['content' => 'sum one']]]], 200)
            ->push(['choices' => [['message' => ['content' => '{"relevance": 50}']]]], 200)
            ->push(['choices' => [['message' => ['content' => 'sum two']]]], 200)
            ->push(['choices' => [['message' => ['content' => '{"relevance": 50}']]]], 200)
            ->push(['choices' => [['message' => ['content' => '{"duplicates": [{"duplicate": 2, "original": 1}]}']]]], 200),
    ]);
    config()->set('ai.providers', ['openai']);
    config()->set('services.openai.key', 'sk-openai-secret');
    config()->set('ai.provider', 'openai');

    $run = TopicRun::factory()->create();
    $original = NewsItem::factory()->create(['topic_run_id' => $run->id, 'id' => 1]);
    $duplicate = NewsItem::factory()->create(['topic_run_id' => $run->id, 'id' => 2]);

    app(AiAnalysisService::class)->analyzeRun($run);

    expect($duplicate->fresh()->ai_duplicate_of_id)->toBe($original->id);
});

test('a run records the ai token usage and provider metadata', function () {
    Http::fake([
        'api.openai.com/*' => Http::sequence()
            ->push([
                'model' => 'gpt-4o-mini',
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5],
                'choices' => [['message' => ['content' => 'A tidy summary.']]],
            ], 200)
            ->push([
                'model' => 'gpt-4o-mini',
                'usage' => ['prompt_tokens' => 8, 'completion_tokens' => 2],
                'choices' => [['message' => ['content' => '{"relevance": 75}']]],
            ], 200),
    ]);
    config()->set('ai.providers', ['openai']);
    config()->set('services.openai.key', 'sk-openai-secret');
    config()->set('ai.provider', 'openai');
    config()->set('ai.analyze_after_search', true);
    config()->set('monitoring.costs.per_result', 0);
    config()->set('monitoring.costs.per_1k_tokens_ai', 2.0);
    (new AIConfigRepository)->forget();

    fakeSearchRegistry([
        new SearchResult('Breaking news', 'https://a.com/x', 'a.com', sourceIdentifier: 'rss'),
    ]);

    $topic = MonitoredTopic::factory()->for(User::factory())->create();

    $run = app(RunMonitoredTopicSearch::class)->execute($topic, RunTrigger::Manual);

    expect($run->provider_usage['ai_tokens'])->toBe(25)
        ->and($run->provider_usage['ai_provider'])->toBe('openai')
        ->and($run->provider_usage['ai_model'])->toBe('gpt-4o-mini')
        ->and((float) $run->estimated_cost)->toBe(0.05);
});
