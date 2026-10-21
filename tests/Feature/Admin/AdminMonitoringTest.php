<?php

use App\Models\MonitoredTopic;
use App\Models\NewsEntry;
use App\Models\NotificationDelivery;
use App\Models\TopicRun;
use App\Models\User;

test('an admin can browse search runs and filter them', function () {
    $admin = User::factory()->admin()->create();

    $successTopic = MonitoredTopic::factory()->create(['name' => 'Success topic']);
    $failedTopic = MonitoredTopic::factory()->create(['name' => 'Failed topic']);

    TopicRun::factory()->for($successTopic, 'monitoredTopic')->create();
    TopicRun::factory()->for($failedTopic, 'monitoredTopic')->failed('Provider exploded')->create();

    $this->actingAs($admin)
        ->get(route('admin.monitoring.runs'))
        ->assertOk()
        ->assertSee('Success topic')
        ->assertSee('Failed topic')
        ->assertSee('Provider exploded');

    $this->actingAs($admin)
        ->get(route('admin.monitoring.runs', ['status' => 'failed']))
        ->assertOk()
        ->assertSee('Failed topic')
        ->assertDontSee('Success topic');
});

test('an admin can browse email deliveries and filter them', function () {
    $admin = User::factory()->admin()->create();
    $sentUser = User::factory()->create(['email' => 'sent@example.com']);
    $failedUser = User::factory()->create(['email' => 'failed@example.com']);

    NotificationDelivery::factory()->for($sentUser)->sent()->create();
    NotificationDelivery::factory()->for($failedUser)->failed()->create();

    $this->actingAs($admin)
        ->get(route('admin.monitoring.deliveries'))
        ->assertOk()
        ->assertSee('sent@example.com')
        ->assertSee('failed@example.com');

    $this->actingAs($admin)
        ->get(route('admin.monitoring.deliveries', ['status' => 'failed']))
        ->assertOk()
        ->assertSee('failed@example.com')
        ->assertDontSee('sent@example.com');
});

test('a non admin cannot browse monitoring pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.monitoring.runs'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.monitoring.deliveries'))->assertForbidden();
});

test('the admin dashboard shows aggregate metrics', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['name' => 'Cost Owner', 'email' => 'owner@example.com']);
    $topic = MonitoredTopic::factory()->for($user)->create();

    TopicRun::factory()->for($topic, 'monitoredTopic')->create([
        'results_count' => 5,
        'estimated_cost' => 0.25,
        'provider_usage' => ['ai_provider' => 'openai', 'ai_model' => 'gpt-4o-mini', 'ai_tokens' => 1200],
    ]);

    NotificationDelivery::factory()->for($user)->sent()->create(['monitored_topic_id' => $topic->id, 'news_entry_id' => NewsEntry::factory()]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Cost Owner')
        ->assertSee('owner@example.com')
        ->assertSee('openai')
        ->assertSee('1,200')
        ->assertSee('0.2500');
});

test('a non admin cannot browse the admin dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
});
