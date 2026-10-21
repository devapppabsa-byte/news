<?php

use App\Models\MonitoredTopic;
use App\Models\NewsItem;
use App\Models\User;

test('a user can mark their own item as read, saved and irrelevant', function () {
    $user = User::factory()->create();
    $topic = MonitoredTopic::factory()->for($user)->create();
    $item = NewsItem::factory()->forTopic($topic)->create();

    $this->actingAs($user)->patch("/news-items/{$item->id}/read")->assertRedirect();
    $this->actingAs($user)->patch("/news-items/{$item->id}/save")->assertRedirect();
    $this->actingAs($user)->patch("/news-items/{$item->id}/irrelevant")->assertRedirect();

    $item->refresh();

    expect($item->is_read)->toBeTrue()
        ->and($item->read_at)->not->toBeNull()
        ->and($item->is_saved)->toBeTrue()
        ->and($item->is_irrelevant)->toBeTrue();
});

test('the actions toggle the state off again', function () {
    $user = User::factory()->create();
    $topic = MonitoredTopic::factory()->for($user)->create();
    $item = NewsItem::factory()->forTopic($topic)->create(['is_read' => true, 'read_at' => now()]);

    $this->actingAs($user)->patch("/news-items/{$item->id}/read")->assertRedirect();

    expect($item->fresh()->is_read)->toBeFalse()
        ->and($item->fresh()->read_at)->toBeNull();
});

test('a user can not modify another user item', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $topic = MonitoredTopic::factory()->for($owner)->create();
    $item = NewsItem::factory()->forTopic($topic)->create();

    $this->actingAs($other)->patch("/news-items/{$item->id}/save")->assertForbidden();
});

test('an admin can modify any item', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $topic = MonitoredTopic::factory()->for($owner)->create();
    $item = NewsItem::factory()->forTopic($topic)->create();

    $this->actingAs($admin)->patch("/news-items/{$item->id}/save")->assertRedirect();

    expect($item->fresh()->is_saved)->toBeTrue();
});

test('a suspended user can not modify items', function () {
    $user = User::factory()->suspended()->create();
    $topic = MonitoredTopic::factory()->for($user)->create();
    $item = NewsItem::factory()->forTopic($topic)->create();

    $this->actingAs($user)->patch("/news-items/{$item->id}/save")->assertForbidden();
});

test('guests are redirected to login', function () {
    $item = NewsItem::factory()->create();

    $this->patch("/news-items/{$item->id}/read")->assertRedirect('/login');
});
