<?php

use App\Enums\KeywordType;
use App\Enums\RunStatus;
use App\Enums\SearchFrequency;
use App\Enums\SourceType;
use App\Enums\TopicStatus;
use App\Models\MonitoredTopic;
use App\Models\TopicKeyword;
use App\Models\TopicRun;
use App\Models\TopicSource;
use App\Models\User;

test('an authenticated user sees the topics index', function () {
    $user = User::factory()->create();
    MonitoredTopic::factory()->for($user)->create(['name' => 'AI Regulation']);

    $this->actingAs($user)
        ->get('/topics')
        ->assertOk()
        ->assertSee('AI Regulation');
});

test('a user only sees their own topics in the index', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    MonitoredTopic::factory()->for($other)->create(['name' => 'Secret Topic']);

    $this->actingAs($user)
        ->get('/topics')
        ->assertOk()
        ->assertDontSee('Secret Topic');
});

test('a user can open the create and edit screens', function () {
    $user = User::factory()->create();
    $topic = MonitoredTopic::factory()->for($user)->create();

    $this->actingAs($user)->get('/topics/create')->assertOk();
    $this->actingAs($user)->get("/topics/{$topic->id}/edit")->assertOk();
});

test('a user can create a monitored topic with keywords and sources', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/topics', [
            'name' => 'Artificial Intelligence',
            'description' => 'News about AI research and policy.',
            'language' => 'es',
            'country' => 'ES',
            'frequency' => SearchFrequency::Daily->value,
            'notify_email' => '1',
            'keywords_include' => "openai\nregulation",
            'keywords_exclude' => 'gossip',
            'sources_preferred' => "reuters.com\nelpais.com",
            'sources_excluded' => 'tabloid.com',
        ])
        ->assertRedirect();

    $topic = MonitoredTopic::query()->where('name', 'Artificial Intelligence')->firstOrFail();

    expect($topic->user_id)->toBe($user->id)
        ->and($topic->slug)->toBe('artificial-intelligence')
        ->and($topic->frequency)->toBe(SearchFrequency::Daily)
        ->and($topic->status)->toBe(TopicStatus::Active)
        ->and($topic->next_run_at)->not->toBeNull()
        ->and($topic->keywords()->where('type', KeywordType::Include)->count())->toBe(2)
        ->and($topic->keywords()->where('type', KeywordType::Exclude)->count())->toBe(1)
        ->and($topic->sources()->where('type', SourceType::Preferred)->count())->toBe(2)
        ->and($topic->sources()->where('type', SourceType::Excluded)->count())->toBe(1);
});

test('topic slugs are unique per user', function () {
    $user = User::factory()->create();

    MonitoredTopic::factory()->for($user)->create([
        'name' => 'Technology',
        'slug' => 'technology',
    ]);

    $this->actingAs($user)
        ->post('/topics', [
            'name' => 'Technology',
            'language' => 'es',
            'frequency' => SearchFrequency::Daily->value,
        ])
        ->assertRedirect();

    $slugs = $user->monitoredTopics()->pluck('slug');

    expect($slugs)->toHaveCount(2)
        ->and($slugs->unique())->toHaveCount(2);
});

test('a custom frequency requires an interval within the configured limits', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/topics', [
            'name' => 'Too frequent',
            'language' => 'es',
            'frequency' => SearchFrequency::Custom->value,
            'custom_interval_minutes' => 1,
        ])
        ->assertSessionHasErrors('custom_interval_minutes');

    expect(MonitoredTopic::query()->where('name', 'Too frequent')->exists())->toBeFalse();
});

test('a custom frequency accepts a valid interval', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/topics', [
            'name' => 'Custom cadence',
            'language' => 'es',
            'frequency' => SearchFrequency::Custom->value,
            'custom_interval_minutes' => 120,
        ])
        ->assertSessionHasNoErrors();

    $topic = MonitoredTopic::query()->where('name', 'Custom cadence')->firstOrFail();

    expect($topic->frequency)->toBe(SearchFrequency::Custom)
        ->and($topic->custom_interval_minutes)->toBe(120);
});

test('invalid domains are rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/topics', [
            'name' => 'Bad sources',
            'language' => 'es',
            'frequency' => SearchFrequency::Daily->value,
            'sources_preferred' => 'not a domain',
        ])
        ->assertSessionHasErrors('sources_preferred');
});

test('a user can update their topic and replace keywords', function () {
    $user = User::factory()->create();
    $topic = MonitoredTopic::factory()->for($user)->create(['name' => 'Old name']);
    TopicKeyword::factory()->for($topic, 'monitoredTopic')->create(['value' => 'stale']);

    $this->actingAs($user)
        ->put("/topics/{$topic->id}", [
            'name' => 'New name',
            'language' => 'en',
            'frequency' => SearchFrequency::Weekly->value,
            'keywords_include' => 'fresh',
        ])
        ->assertRedirect();

    $topic->refresh();

    expect($topic->name)->toBe('New name')
        ->and($topic->frequency)->toBe(SearchFrequency::Weekly)
        ->and($topic->keywords()->pluck('value')->all())->toBe(['fresh']);
});

test('a user can pause and resume their topic', function () {
    $user = User::factory()->create();
    $topic = MonitoredTopic::factory()->for($user)->create();

    $this->actingAs($user)
        ->patch("/topics/{$topic->id}/pause")
        ->assertRedirect();

    expect($topic->fresh()->status)->toBe(TopicStatus::Paused)
        ->and($topic->fresh()->next_run_at)->toBeNull();

    $this->actingAs($user)
        ->patch("/topics/{$topic->id}/resume")
        ->assertRedirect();

    expect($topic->fresh()->status)->toBe(TopicStatus::Active)
        ->and($topic->fresh()->next_run_at)->not->toBeNull();
});

test('a user can delete their topic', function () {
    $user = User::factory()->create();
    $topic = MonitoredTopic::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete("/topics/{$topic->id}")
        ->assertRedirect('/topics');

    expect(MonitoredTopic::query()->find($topic->id))->toBeNull();
});

test('a manual run records a topic run and refreshes the schedule', function () {
    Http::preventStrayRequests();
    Http::fake(['*' => Http::response('<?xml version="1.0"?><rss version="2.0"><channel></channel></rss>', 200)]);

    $user = User::factory()->create();
    $topic = MonitoredTopic::factory()->for($user)->create([
        'frequency' => SearchFrequency::Daily,
        'last_run_at' => null,
        'next_run_at' => null,
    ]);

    $this->actingAs($user)
        ->post("/topics/{$topic->id}/run")
        ->assertRedirect();

    $topic->refresh();

    expect($topic->runs()->count())->toBe(1)
        ->and($topic->runs()->first()->status)->toBe(RunStatus::Success)
        ->and($topic->last_run_at)->not->toBeNull()
        ->and($topic->next_run_at)->not->toBeNull();
});

test('a user can not view another users topic', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $topic = MonitoredTopic::factory()->for($owner)->create();

    $this->actingAs($intruder)->get("/topics/{$topic->id}")->assertForbidden();
    $this->actingAs($intruder)->get("/topics/{$topic->id}/edit")->assertForbidden();
});

test('a user can not update or delete another users topic', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $topic = MonitoredTopic::factory()->for($owner)->create();

    $this->actingAs($intruder)
        ->put("/topics/{$topic->id}", [
            'name' => 'Hijacked',
            'language' => 'es',
            'frequency' => SearchFrequency::Daily->value,
        ])
        ->assertForbidden();

    $this->actingAs($intruder)
        ->delete("/topics/{$topic->id}")
        ->assertForbidden();

    expect($topic->fresh()->name)->not->toBe('Hijacked');
});

test('a suspended user can read topics but not mutate them', function () {
    $user = User::factory()->suspended()->create();
    $topic = MonitoredTopic::factory()->for($user)->create();

    $this->actingAs($user)->get('/topics')->assertOk();
    $this->actingAs($user)->get("/topics/{$topic->id}")->assertOk();

    $this->actingAs($user)
        ->post('/topics', [
            'name' => 'Blocked',
            'language' => 'es',
            'frequency' => SearchFrequency::Daily->value,
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->patch("/topics/{$topic->id}/pause")
        ->assertForbidden();

    $this->actingAs($user)
        ->post("/topics/{$topic->id}/run")
        ->assertForbidden();
});

test('an admin can manage another users topic', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    $topic = MonitoredTopic::factory()->for($owner)->create();

    $this->actingAs($admin)->get("/topics/{$topic->id}")->assertOk();

    $this->actingAs($admin)
        ->patch("/topics/{$topic->id}/pause")
        ->assertRedirect();

    expect($topic->fresh()->status)->toBe(TopicStatus::Paused);
});

test('the due scope selects only active topics whose next run has passed', function () {
    $user = User::factory()->create();

    $due = MonitoredTopic::factory()->for($user)->due()->create();
    $future = MonitoredTopic::factory()->for($user)->create([
        'next_run_at' => now()->addDay(),
    ]);
    $paused = MonitoredTopic::factory()->for($user)->due()->paused()->create();

    $dueIds = MonitoredTopic::query()->due()->pluck('id');

    expect($dueIds)->toContain($due->id)
        ->not->toContain($future->id)
        ->not->toContain($paused->id);
});

test('run history is visible on the topic page', function () {
    $user = User::factory()->create();
    $topic = MonitoredTopic::factory()->for($user)->create();
    TopicRun::factory()->for($topic, 'monitoredTopic')->create([
        'status' => RunStatus::Success,
        'results_count' => 7,
    ]);
    TopicSource::factory()->for($topic, 'monitoredTopic')->create();

    $this->actingAs($user)
        ->get("/topics/{$topic->id}")
        ->assertOk()
        ->assertSee('7');
});
