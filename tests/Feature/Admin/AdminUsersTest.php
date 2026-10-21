<?php

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an admin can browse the users list', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['name' => 'Jane Search']);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('Jane Search');
});

test('the users list can be searched and filtered', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['name' => 'Match Me', 'email' => 'match@example.com']);
    User::factory()->create(['name' => 'Other', 'email' => 'other@example.com']);

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['search' => 'match@example.com']))
        ->assertOk()
        ->assertSee('Match Me')
        ->assertDontSee('other@example.com');
});

test('an admin can view a user detail page', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.users.show', $user))
        ->assertOk()
        ->assertSee($user->email);
});

test('an admin can suspend and reactivate a regular user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.suspend', $user))
        ->assertRedirect(route('admin.users.show', $user));

    $user->refresh();
    expect($user->status)->toBe(AccountStatus::Suspended)
        ->and($user->suspended_at)->not->toBeNull();

    $this->actingAs($admin)
        ->post(route('admin.users.reactivate', $user))
        ->assertRedirect(route('admin.users.show', $user));

    $user->refresh();
    expect($user->status)->toBe(AccountStatus::Active)
        ->and($user->suspended_at)->toBeNull();
});

test('an admin cannot suspend themselves', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.suspend', $admin))
        ->assertForbidden();

    expect($admin->fresh()->status)->toBe(AccountStatus::Active);
});

test('an admin cannot suspend another admin', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.suspend', $other))
        ->assertForbidden();

    expect($other->fresh()->status)->toBe(AccountStatus::Active);
});

test('a regular user cannot access the user management area', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.users.show', $target))->assertForbidden();
    $this->actingAs($user)->post(route('admin.users.suspend', $target))->assertForbidden();
});

test('a suspended admin cannot perform administrative writes', function () {
    $admin = User::factory()->admin()->create(['status' => AccountStatus::Suspended]);
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.suspend', $target))
        ->assertForbidden();
});
