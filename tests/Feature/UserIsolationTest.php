<?php

use App\Models\MonitoredTopic;
use App\Models\NewsItem;
use App\Models\Subscription;
use App\Models\User;

test('a user can not view another users monitored topic', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $topic = MonitoredTopic::factory()->for($owner)->create();

    expect($intruder->can('view', $topic))->toBeFalse();
    expect($owner->can('view', $topic))->toBeTrue();
});

test('a user can not update another users monitored topic', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $topic = MonitoredTopic::factory()->for($owner)->create();

    expect($intruder->can('update', $topic))->toBeFalse();
    expect($owner->can('update', $topic))->toBeTrue();
});

test('a user can not view another users news item', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $topic = MonitoredTopic::factory()->for($owner)->create();
    $newsItem = NewsItem::factory()->forTopic($topic)->create();

    expect($intruder->can('view', $newsItem))->toBeFalse();
    expect($owner->can('view', $newsItem))->toBeTrue();
});

test('a user can not view another users subscription', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $topic = MonitoredTopic::factory()->for($owner)->create();
    $subscription = Subscription::factory()->for($owner)->for($topic)->create();

    expect($intruder->can('view', $subscription))->toBeFalse();
    expect($owner->can('view', $subscription))->toBeTrue();
});

test('a user can not update another users preferences', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $preference = $owner->preference()->create();

    expect($intruder->can('update', $preference))->toBeFalse();
    expect($owner->can('update', $preference))->toBeTrue();
});
