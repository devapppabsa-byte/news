<?php

use App\Models\User;

test('guests are redirected from the admin panel', function () {
    $this->get('/admin')->assertRedirect('/login');
});

test('regular users can not access the admin panel', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

test('admins can access the admin panel', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/admin')->assertOk();
});

test('admins are granted every ability through the gate', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->create();
    $topic = $other->monitoredTopics()->create([
        'name' => 'Politics',
        'slug' => 'politics',
    ]);

    expect($admin->can('update', $topic))->toBeTrue();
    expect($admin->can('delete', $topic))->toBeTrue();
});

test('regular users can not update other users', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($user)
        ->patch('/profile', ['name' => 'Hacked', 'email' => $other->email])
        ->assertStatus(302);

    expect($other->fresh()->name)->not->toBe('Hacked');
});

test('regular users can update their own profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/profile', ['name' => 'My New Name', 'email' => $user->email])
        ->assertSessionHasNoErrors();

    expect($user->fresh()->name)->toBe('My New Name');
});
