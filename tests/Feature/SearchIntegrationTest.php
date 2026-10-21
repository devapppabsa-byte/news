<?php

use App\Actions\RunMonitoredTopicSearch;
use App\DTO\SearchResult;
use App\Enums\RunStatus;
use App\Enums\RunTrigger;
use App\Exceptions\SearchProviderException;
use App\Models\MonitoredTopic;
use App\Models\NewsItem;
use App\Models\SearchCredential;
use App\Models\User;
use App\Services\Search\CredentialRepository;
use App\Services\Search\ProviderRegistry;

test('the registry merges and deduplicates results across providers', function () {
    $registry = Mockery::mock(ProviderRegistry::class);
    $this->instance(ProviderRegistry::class, $registry);

    $registry->shouldReceive('search')->andReturn([
        'results' => [
            new SearchResult('One', 'https://a.com/x', 'a.com', sourceIdentifier: 'rss'),
            new SearchResult('Two', 'https://b.com/y', 'b.com', sourceIdentifier: 'brave'),
        ],
        'errors' => [],
    ]);

    $user = User::factory()->create();
    $topic = MonitoredTopic::factory()->for($user)->create();

    $run = app(RunMonitoredTopicSearch::class)->execute($topic, RunTrigger::Manual);

    expect($run->status)->toBe(RunStatus::Success)
        ->and($run->results_count)->toBe(2)
        ->and($topic->fresh()->last_run_at)->not->toBeNull()
        ->and(NewsItem::query()->where('topic_id', $topic->id)->count())->toBe(2);
});

test('running the same search twice does not duplicate results', function () {
    $registry = Mockery::mock(ProviderRegistry::class);
    $this->instance(ProviderRegistry::class, $registry);

    $registry->shouldReceive('search')->andReturn([
        'results' => [new SearchResult('One', 'https://a.com/x', 'a.com', sourceIdentifier: 'rss')],
        'errors' => [],
    ]);

    $user = User::factory()->create();
    $topic = MonitoredTopic::factory()->for($user)->create();
    $action = app(RunMonitoredTopicSearch::class);

    $action->execute($topic, RunTrigger::Manual);
    $action->execute($topic, RunTrigger::Manual);

    expect(NewsItem::query()->where('topic_id', $topic->id)->count())->toBe(1);
});

test('a failing provider marks the run as failed with a sanitized message', function () {
    $registry = Mockery::mock(ProviderRegistry::class);
    $this->instance(ProviderRegistry::class, $registry);

    $registry->shouldReceive('search')->andThrow(
        new SearchProviderException('brave', 'Connection failed: token sk-secret123 invalid'),
    );

    $user = User::factory()->create();
    $topic = MonitoredTopic::factory()->for($user)->create();

    $run = app(RunMonitoredTopicSearch::class)->execute($topic, RunTrigger::Manual);

    expect($run->status)->toBe(RunStatus::Failed)
        ->and($run->error_message)->not->toContain('sk-secret123')
        ->and($run->duration_ms)->not->toBeNull()
        ->and($topic->fresh()->last_run_at)->toBeNull();
});

test('results are stored with source metadata and shown on the topic page', function () {
    $user = User::factory()->create();
    $topic = MonitoredTopic::factory()->for($user)->create();
    $run = $topic->runs()->create(['trigger' => RunTrigger::Manual, 'status' => RunStatus::Success]);

    NewsItem::factory()->forTopic($topic)->create([
        'topic_run_id' => $run->id,
        'title' => 'Visible result',
        'url' => 'https://source.com/article',
        'source_domain' => 'source.com',
        'source_identifier' => 'tavily',
    ]);

    $this->actingAs($user)
        ->get("/topics/{$topic->id}")
        ->assertOk()
        ->assertSee('Visible result')
        ->assertSee('source.com');
});

test('an admin can store an encrypted search credential', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put('/admin/search', [
            'credentials' => [
                'brave' => ['key' => 'super-secret-brave-key'],
            ],
        ])
        ->assertRedirect();

    expect(SearchCredential::query()->where('provider', 'brave')->exists())->toBeTrue();

    $stored = DB::table('search_credentials')->where('provider', 'brave')->value('value');

    expect($stored)->not->toBe('super-secret-brave-key')
        ->and(app(CredentialRepository::class)->get('brave', 'key'))->toBe('super-secret-brave-key');
});

test('the credential value is never rendered in the admin form', function () {
    $admin = User::factory()->admin()->create();
    SearchCredential::factory()->create(['provider' => 'brave', 'name' => 'key', 'value' => 'top-secret']);

    $this->actingAs($admin)
        ->get('/admin/search')
        ->assertOk()
        ->assertDontSee('top-secret')
        ->assertSee('configured');
});

test('a non admin can not access search settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin/search')->assertForbidden();
    $this->actingAs($user)->put('/admin/search', [])->assertForbidden();
});
