<?php

use App\Models\User;

test('a suspended user can still authenticate', function () {
    $user = User::factory()->suspended()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
});

test('a suspended user can read their profile', function () {
    $user = User::factory()->suspended()->create();

    $this->actingAs($user)->get('/profile')->assertOk();
});

test('a suspended user can not update their profile', function () {
    $user = User::factory()->suspended()->create();

    $this->actingAs($user)
        ->patch('/profile', ['name' => 'Nope', 'email' => $user->email])
        ->assertForbidden();

    expect($user->fresh()->name)->not->toBe('Nope');
});

test('a suspended user can not delete their account', function () {
    $user = User::factory()->suspended()->create();

    $this->actingAs($user)
        ->delete('/profile', ['password' => 'password'])
        ->assertForbidden();

    $this->assertNotNull($user->fresh());
});

test('an active user is not blocked by the readonly middleware', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/profile', ['name' => 'Allowed', 'email' => $user->email])
        ->assertSessionHasNoErrors();

    expect($user->fresh()->name)->toBe('Allowed');
});
