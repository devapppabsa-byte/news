<?php

use App\Models\SearchCredential;
use App\Models\User;
use App\Services\Search\CredentialRepository;

test('an admin can open the credential removal confirmation', function () {
    $admin = User::factory()->admin()->create();
    SearchCredential::factory()->create(['provider' => 'openai', 'name' => 'key', 'value' => 'secret']);

    $this->actingAs($admin)
        ->get(route('admin.credentials.confirm', ['scope' => 'ai', 'provider' => 'openai', 'name' => 'key']))
        ->assertOk()
        ->assertSee('OpenAI API key')
        ->assertSee('Stored in the panel');
});

test('removing a stored credential reveals the environment fallback', function () {
    $admin = User::factory()->admin()->create();
    SearchCredential::factory()->create(['provider' => 'openai', 'name' => 'key', 'value' => 'stored-secret']);
    config()->set('services.openai.key', 'env-secret');

    $this->actingAs($admin)
        ->post(route('admin.credentials.destroy', ['scope' => 'ai', 'provider' => 'openai', 'name' => 'key']))
        ->assertRedirect(route('admin.ai.settings'))
        ->assertSessionHas('status', 'credential-removed');

    expect(SearchCredential::query()->where('provider', 'openai')->exists())->toBeFalse()
        ->and(app(CredentialRepository::class)->status('openai', 'key', 'env-secret'))->toBe('environment');
});

test('an unknown credential cannot be removed', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.credentials.confirm', ['scope' => 'ai', 'provider' => 'openai', 'name' => 'unknown']))
        ->assertNotFound();

    $this->actingAs($admin)
        ->post(route('admin.credentials.destroy', ['scope' => 'ai', 'provider' => 'openai', 'name' => 'unknown']))
        ->assertNotFound();
});

test('a non admin cannot remove credentials', function () {
    $user = User::factory()->create();
    SearchCredential::factory()->create(['provider' => 'openai', 'name' => 'key', 'value' => 'secret']);

    $this->actingAs($user)
        ->get(route('admin.credentials.confirm', ['scope' => 'ai', 'provider' => 'openai', 'name' => 'key']))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('admin.credentials.destroy', ['scope' => 'ai', 'provider' => 'openai', 'name' => 'key']))
        ->assertForbidden();

    expect(SearchCredential::query()->where('provider', 'openai')->exists())->toBeTrue();
});
