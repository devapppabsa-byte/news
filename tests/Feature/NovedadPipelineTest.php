<?php

use App\DTO\SearchResult;
use App\Enums\NewsItemStatus;
use App\Enums\RunStatus;
use App\Enums\RunTrigger;
use App\Models\MonitoredTopic;
use App\Models\NewsEntry;
use App\Models\NewsItem;
use App\Models\User;
use App\Services\AI\AiAnalysisService;
use App\Services\Novedades\NovedadPipeline;
use Illuminate\Support\Facades\Http;

function runPipeline(MonitoredTopic $topic, array $results): array
{
    $run = $topic->runs()->create(['trigger' => RunTrigger::Manual, 'status' => RunStatus::Running]);

    return app(NovedadPipeline::class)->process($topic, $run, $results);
}

test('a brand new result creates one global entry linked as new', function () {
    $analysis = Mockery::mock(AiAnalysisService::class);
    $analysis->shouldReceive('assessNovedad')->andReturn([
        'relevance' => 80, 'summary' => 'A summary', 'is_irrelevant' => false, 'reason' => null,
    ]);
    $this->instance(AiAnalysisService::class, $analysis);

    $topic = MonitoredTopic::factory()->for(User::factory())->create();

    $summary = runPipeline($topic, [
        new SearchResult('Fresh story', 'https://a.com/x', 'a.com', 'first snippet'),
    ]);

    expect($summary['new'])->toBe(1)
        ->and(NewsEntry::query()->count())->toBe(1)
        ->and(NewsItem::query()->where('topic_id', $topic->id)->count())->toBe(1)
        ->and(NewsItem::query()->first()->novedad_status)->toBe(NewsItemStatus::New)
        ->and(NewsEntry::query()->first()->summary)->toBe('A summary');
});

test('the same result in a second run is marked existing without duplicating', function () {
    $analysis = Mockery::mock(AiAnalysisService::class);
    $analysis->shouldReceive('assessNovedad')->andReturn([
        'relevance' => 80, 'summary' => 'S', 'is_irrelevant' => false, 'reason' => null,
    ]);
    $this->instance(AiAnalysisService::class, $analysis);

    $topic = MonitoredTopic::factory()->for(User::factory())->create();
    $result = new SearchResult('Story', 'https://a.com/x', 'a.com', 'snippet');

    runPipeline($topic, [$result]);
    $summary = runPipeline($topic, [$result]);

    expect($summary['existing'])->toBe(1)
        ->and(NewsEntry::query()->count())->toBe(1)
        ->and(NewsItem::query()->where('topic_id', $topic->id)->count())->toBe(1);
});

test('a changed url with identical content is not treated as new', function () {
    $analysis = Mockery::mock(AiAnalysisService::class);
    $analysis->shouldReceive('assessNovedad')->andReturn([
        'relevance' => 90, 'summary' => 'S', 'is_irrelevant' => false, 'reason' => null,
    ]);
    $this->instance(AiAnalysisService::class, $analysis);

    $topic = MonitoredTopic::factory()->for(User::factory())->create();

    runPipeline($topic, [new SearchResult('Shared headline', 'https://a.com/1', 'a.com', 'same body')]);
    $summary = runPipeline($topic, [new SearchResult('Shared headline', 'https://b.com/2', 'b.com', 'same body')]);

    expect(NewsEntry::query()->count())->toBe(1)
        ->and($summary['new'])->toBe(0)
        ->and($summary['existing'])->toBe(1);
});

test('a shared entry can be linked to multiple topics without duplicating globally', function () {
    $analysis = Mockery::mock(AiAnalysisService::class);
    $analysis->shouldReceive('assessNovedad')->andReturn([
        'relevance' => 90, 'summary' => 'S', 'is_irrelevant' => false, 'reason' => null,
    ]);
    $this->instance(AiAnalysisService::class, $analysis);

    $user = User::factory()->create();
    $topicA = MonitoredTopic::factory()->for($user)->create();
    $topicB = MonitoredTopic::factory()->for($user)->create();
    $result = new SearchResult('Cross topic', 'https://a.com/x', 'a.com', 'body');

    runPipeline($topicA, [$result]);
    runPipeline($topicB, [$result]);

    expect(NewsEntry::query()->count())->toBe(1)
        ->and(NewsItem::query()->count())->toBe(2);
});

test('a low relevance result is marked irrelevant', function () {
    $analysis = Mockery::mock(AiAnalysisService::class);
    $analysis->shouldReceive('assessNovedad')->andReturn([
        'relevance' => 5, 'summary' => 'S', 'is_irrelevant' => false, 'reason' => null,
    ]);
    $this->instance(AiAnalysisService::class, $analysis);

    $topic = MonitoredTopic::factory()->for(User::factory())->create();

    $summary = runPipeline($topic, [new SearchResult('Noise', 'https://a.com/x', 'a.com', 'b')]);

    expect($summary['irrelevant'])->toBe(1)
        ->and(NewsItem::query()->first()->novedad_status)->toBe(NewsItemStatus::Irrelevant);
});

test('a failing assessment keeps the entry as stored', function () {
    $analysis = Mockery::mock(AiAnalysisService::class);
    $analysis->shouldReceive('assessNovedad')->andThrow(new RuntimeException('boom'));
    $this->instance(AiAnalysisService::class, $analysis);

    $topic = MonitoredTopic::factory()->for(User::factory())->create();

    $summary = runPipeline($topic, [new SearchResult('Story', 'https://a.com/x', 'a.com', 'b')]);

    expect($summary['new'])->toBe(1)
        ->and(NewsItem::query()->first()->novedad_status)->toBe(NewsItemStatus::New);
});

test('source availability is recorded when enabled', function () {
    config()->set('novedades.source_check.enabled', true);
    Http::fake(['*' => Http::response('', 200)]);

    $analysis = Mockery::mock(AiAnalysisService::class);
    $analysis->shouldReceive('assessNovedad')->andReturn([
        'relevance' => 80, 'summary' => 'S', 'is_irrelevant' => false, 'reason' => null,
    ]);
    $this->instance(AiAnalysisService::class, $analysis);

    $topic = MonitoredTopic::factory()->for(User::factory())->create();

    runPipeline($topic, [new SearchResult('Story', 'https://a.com/x', 'a.com', 'b')]);

    $entry = NewsEntry::query()->first();

    expect($entry->source_available)->toBeTrue()
        ->and($entry->source_checked_at)->not->toBeNull();
});

test('an unreachable source is flagged but the entry is kept', function () {
    config()->set('novedades.source_check.enabled', true);
    Http::fake(['*' => Http::response('', 500)]);

    $analysis = Mockery::mock(AiAnalysisService::class);
    $analysis->shouldReceive('assessNovedad')->andReturn([
        'relevance' => 80, 'summary' => 'S', 'is_irrelevant' => false, 'reason' => null,
    ]);
    $this->instance(AiAnalysisService::class, $analysis);

    $topic = MonitoredTopic::factory()->for(User::factory())->create();

    runPipeline($topic, [new SearchResult('Story', 'https://a.com/x', 'a.com', 'b')]);

    expect(NewsEntry::query()->count())->toBe(1)
        ->and(NewsEntry::query()->first()->source_available)->toBeFalse();
});
