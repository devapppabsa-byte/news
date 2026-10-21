<?php

use App\Actions\RunMonitoredTopicSearch;
use App\Models\MonitoredTopic;
use App\Models\User;
use App\Services\AI\AiAnalysisService;
use App\Services\Novedades\NovedadPipeline;
use App\Services\Search\ProviderRegistry;
use App\Services\Settings\SettingsRepository;
use App\Support\CostEstimator;
use App\Support\SettingKeys;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the topics per user limit is enforced on the backend', function () {
    $user = User::factory()->create();

    app(SettingsRepository::class)->set(SettingKeys::MAX_TOPICS_PER_USER, 2);

    MonitoredTopic::factory()->for($user)->count(2)->create();

    $this->actingAs($user)
        ->post(route('topics.store'), [
            'name' => 'Third topic',
            'language' => 'es',
            'frequency' => 'daily',
        ])
        ->assertSessionHasErrors('name');

    expect($user->monitoredTopics()->count())->toBe(2);
});

test('a zero topics limit means unlimited', function () {
    $user = User::factory()->create();

    app(SettingsRepository::class)->set(SettingKeys::MAX_TOPICS_PER_USER, 0);

    MonitoredTopic::factory()->for($user)->count(3)->create();

    $this->actingAs($user)
        ->post(route('topics.store'), [
            'name' => 'Fourth topic',
            'language' => 'es',
            'frequency' => 'daily',
        ])
        ->assertRedirect(route('topics.show', $user->monitoredTopics()->latest('id')->first()));

    expect($user->monitoredTopics()->count())->toBe(4);
});

test('the custom interval bounds are enforced from the settings', function () {
    $user = User::factory()->create();

    app(SettingsRepository::class)->set(SettingKeys::CUSTOM_INTERVAL_MIN, 60);
    app(SettingsRepository::class)->set(SettingKeys::CUSTOM_INTERVAL_MAX, 120);

    $this->actingAs($user)
        ->post(route('topics.store'), [
            'name' => 'Too frequent',
            'language' => 'es',
            'frequency' => 'custom',
            'custom_interval_minutes' => 30,
        ])
        ->assertSessionHasErrors('custom_interval_minutes');

    $this->actingAs($user)
        ->post(route('topics.store'), [
            'name' => 'Allowed interval',
            'language' => 'es',
            'frequency' => 'custom',
            'custom_interval_minutes' => 90,
        ])
        ->assertSessionHasNoErrors();
});

test('frequencies removed from the settings are no longer selectable', function () {
    $user = User::factory()->create();

    app(SettingsRepository::class)->set(SettingKeys::FREQUENCIES, ['daily']);

    $this->actingAs($user)
        ->post(route('topics.store'), [
            'name' => 'Hourly topic',
            'language' => 'es',
            'frequency' => 'hourly',
        ])
        ->assertSessionHasErrors('frequency');

    $this->actingAs($user)
        ->post(route('topics.store'), [
            'name' => 'Daily topic',
            'language' => 'es',
            'frequency' => 'daily',
        ])
        ->assertSessionHasNoErrors();
});

test('the search result limit comes from the settings', function () {
    app(SettingsRepository::class)->set(SettingKeys::SEARCH_MAX_RESULTS, 7);

    $action = new RunMonitoredTopicSearch(
        app(ProviderRegistry::class),
        app(AiAnalysisService::class),
        app(NovedadPipeline::class),
        app(CostEstimator::class),
        app(SettingsRepository::class),
    );

    $method = new ReflectionMethod($action, 'buildQuery');
    $method->setAccessible(true);

    $topic = MonitoredTopic::factory()->create();

    expect($method->invoke($action, $topic)->limit)->toBe(7);
});

test('the cost estimator uses the configured rates', function () {
    app(SettingsRepository::class)->set(SettingKeys::COST_PER_RESULT, 0.5);
    app(SettingsRepository::class)->set(SettingKeys::COST_PER_1K_TOKENS, 2);

    $cost = app(CostEstimator::class)->estimate(['results' => 4, 'ai_tokens' => 2000]);

    expect($cost)->toBe(6.0);
});
