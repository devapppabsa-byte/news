<?php

use App\Models\User;
use App\Services\Settings\SettingsRepository;
use App\Support\SettingKeys;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;

uses(RefreshDatabase::class);

function settingsPayload(array $overrides = []): array
{
    $flat = array_merge([
        SettingKeys::CUSTOM_INTERVAL_MIN => 15,
        SettingKeys::CUSTOM_INTERVAL_MAX => 43200,
        SettingKeys::FREQUENCIES => ['hourly', 'daily', 'weekly', 'manual', 'custom'],
        SettingKeys::MAX_TOPICS_PER_USER => 0,
        SettingKeys::SEARCH_MAX_RESULTS => 10,
        SettingKeys::SEARCH_TIMEOUT => 10,
        SettingKeys::SEARCH_RETRIES => 2,
        SettingKeys::CONCURRENCY_MAX_GLOBAL => 5,
        SettingKeys::CONCURRENCY_MAX_PER_USER => 2,
        SettingKeys::NOTIFICATIONS_MAX_PER_DAY => 20,
        SettingKeys::DIGEST_AT => 8,
        SettingKeys::DIGEST_MAX_ITEMS => 25,
        SettingKeys::COST_PER_RESULT => 0,
        SettingKeys::COST_PER_1K_TOKENS => 0,
        SettingKeys::RETENTION_TOPIC_RUNS_DAYS => 90,
        SettingKeys::RETENTION_DELIVERIES_DAYS => 90,
        SettingKeys::RETENTION_NEWS_DAYS => 180,
        SettingKeys::METRICS_WINDOW_DAYS => 30,
    ], $overrides);

    $payload = [];

    foreach ($flat as $key => $value) {
        Arr::set($payload, $key, $value);
    }

    return $payload;
}

test('a setting falls back to the config value when nothing is stored', function () {
    $value = app(SettingsRepository::class)->get(SettingKeys::SEARCH_MAX_RESULTS);

    expect($value)->toBe(config('search.max_results'));
});

test('a stored setting overrides the config value', function () {
    app(SettingsRepository::class)->set(SettingKeys::SEARCH_MAX_RESULTS, 25);

    expect(app(SettingsRepository::class)->integer(SettingKeys::SEARCH_MAX_RESULTS))->toBe(25);
});

test('a setting with a literal default resolves without config', function () {
    expect(app(SettingsRepository::class)->integer(SettingKeys::MAX_TOPICS_PER_USER))->toBe(0)
        ->and(app(SettingsRepository::class)->integer(SettingKeys::RETENTION_TOPIC_RUNS_DAYS))->toBe(90);
});

test('an admin can read the global settings page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.settings'))
        ->assertOk()
        ->assertSee('Global settings')
        ->assertSee('name="monitoring[custom_interval][min_minutes]"', false)
        ->assertSee('name="topics[max_per_user]"', false)
        ->assertSee('name="monitoring[frequencies][]"', false);
});

test('an admin can save the global settings', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), settingsPayload([
            SettingKeys::MAX_TOPICS_PER_USER => 3,
            SettingKeys::SEARCH_MAX_RESULTS => 25,
            SettingKeys::FREQUENCIES => ['daily', 'weekly'],
        ]))
        ->assertRedirect(route('admin.settings'));

    expect(app(SettingsRepository::class)->integer(SettingKeys::MAX_TOPICS_PER_USER))->toBe(3)
        ->and(app(SettingsRepository::class)->integer(SettingKeys::SEARCH_MAX_RESULTS))->toBe(25)
        ->and(app(SettingsRepository::class)->array(SettingKeys::FREQUENCIES))->toBe(['daily', 'weekly']);
});

test('the settings form survives a real browser form encoding', function () {
    $admin = User::factory()->admin()->create();

    parse_str(
        http_build_query(settingsPayload([SettingKeys::MAX_TOPICS_PER_USER => 4])),
        $parsed,
    );

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), $parsed)
        ->assertRedirect(route('admin.settings'))
        ->assertSessionHasNoErrors();

    expect(app(SettingsRepository::class)->integer(SettingKeys::MAX_TOPICS_PER_USER))->toBe(4)
        ->and(app(SettingsRepository::class)->integer(SettingKeys::CUSTOM_INTERVAL_MIN))->toBe(15);
});

test('an admin can restore the default values', function () {
    $admin = User::factory()->admin()->create();
    $settings = app(SettingsRepository::class);

    $settings->set(SettingKeys::SEARCH_MAX_RESULTS, 25);
    $settings->set(SettingKeys::MAX_TOPICS_PER_USER, 3);

    $this->actingAs($admin)
        ->post(route('admin.settings.reset'))
        ->assertRedirect(route('admin.settings'));

    expect($settings->integer(SettingKeys::SEARCH_MAX_RESULTS))->toBe((int) config('search.max_results'))
        ->and($settings->integer(SettingKeys::MAX_TOPICS_PER_USER))->toBe(0);
});

test('a regular user cannot access the global settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.settings'))->assertForbidden();
    $this->actingAs($user)->put(route('admin.settings.update'), settingsPayload())->assertForbidden();
    $this->actingAs($user)->post(route('admin.settings.reset'))->assertForbidden();
});

test('out of range settings are rejected', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), settingsPayload([
            SettingKeys::SEARCH_MAX_RESULTS => 0,
            SettingKeys::DIGEST_AT => 30,
        ]))
        ->assertSessionHasErrors([SettingKeys::SEARCH_MAX_RESULTS, SettingKeys::DIGEST_AT]);
});

test('the minimum interval cannot exceed the maximum interval', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), settingsPayload([
            SettingKeys::CUSTOM_INTERVAL_MIN => 500,
            SettingKeys::CUSTOM_INTERVAL_MAX => 100,
        ]))
        ->assertSessionHasErrors(SettingKeys::CUSTOM_INTERVAL_MAX);
});
