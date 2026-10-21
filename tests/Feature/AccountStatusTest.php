<?php

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\MonitoredTopic;
use App\Models\Preference;
use App\Models\Subscription;
use App\Models\User;
use App\Policies\UserPolicy;

test('a newly registered user is a regular active user', function () {
    $this->post('/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'jane@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->role)->toBe(UserRole::User);
    expect($user->status)->toBe(AccountStatus::Active);
});

test('the user model exposes role and status helpers', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $suspended = User::factory()->suspended()->create();

    expect($admin->isAdmin())->toBeTrue();
    expect($user->isAdmin())->toBeFalse();
    expect($user->isActive())->toBeTrue();
    expect($suspended->isSuspended())->toBeTrue();
    expect($suspended->suspended_at)->not->toBeNull();
});

test('a user owns their topics news preferences and subscriptions', function () {
    $user = User::factory()->create();
    $topic = MonitoredTopic::factory()->for($user)->create();
    $newsItem = $user->newsItems()->create([
        'topic_id' => $topic->id,
        'title' => 'Hello',
        'slug' => 'hello',
        'body' => 'Body',
    ]);
    Preference::factory()->for($user)->create();
    Subscription::factory()->for($user)->for($topic)->create();

    expect($user->monitoredTopics()->count())->toBe(1);
    expect($user->newsItems()->count())->toBe(1);
    expect($user->preference)->not->toBeNull();
    expect($user->subscribedTopics()->count())->toBe(1);
    expect($newsItem->user->is($user))->toBeTrue();
});

test('an admin can not be suspended by another admin', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->admin()->create();

    expect((new UserPolicy)->suspend($admin, $target))->toBeFalse();
    expect((new UserPolicy)->suspend($admin, User::factory()->create()))->toBeTrue();
});
