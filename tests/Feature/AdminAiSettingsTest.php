<?php

use App\Models\AiSetting;
use App\Models\SearchCredential;
use App\Models\User;
use App\Services\Search\CredentialRepository;

test('an admin can store ai settings and encrypted keys', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put('/admin/ai', [
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'search_mode' => 'external',
            'max_tokens' => 2048,
            'timeout' => 45,
            'fallback_provider' => 'openrouter',
            'fallback_model' => 'openai/gpt-4o-mini',
            'credentials' => [
                'openai' => ['key' => 'super-secret-openai-key'],
            ],
        ])
        ->assertRedirect();

    $setting = AiSetting::query()->where('is_active', true)->first();

    expect($setting->provider)->toBe('openai')
        ->and($setting->max_tokens)->toBe(2048)
        ->and($setting->fallback_provider)->toBe('openrouter');

    $stored = DB::table('search_credentials')->where('provider', 'openai')->value('value');

    expect($stored)->not->toBe('super-secret-openai-key')
        ->and(app(CredentialRepository::class)->get('openai', 'key'))->toBe('super-secret-openai-key');
});

test('the ai keys are never rendered in the admin form', function () {
    $admin = User::factory()->admin()->create();
    SearchCredential::factory()->create(['provider' => 'anthropic', 'name' => 'key', 'value' => 'top-secret-anthropic']);

    $this->actingAs($admin)
        ->get('/admin/ai')
        ->assertOk()
        ->assertDontSee('top-secret-anthropic')
        ->assertSee('configured');
});

test('a non admin can not access ai settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin/ai')->assertForbidden();
    $this->actingAs($user)->put('/admin/ai', [])->assertForbidden();
});

test('ai settings are validated', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put('/admin/ai', [
            'provider' => 'unsupported',
            'model' => '',
            'search_mode' => 'invalid',
            'max_tokens' => 1,
            'timeout' => 1,
        ])
        ->assertSessionHasErrors(['provider', 'model', 'search_mode', 'max_tokens', 'timeout']);
});
