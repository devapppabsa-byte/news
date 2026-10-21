<?php

use App\Enums\NewsItemStatus;
use App\Jobs\SendWebPushNotificationJob;
use App\Models\MonitoredTopic;
use App\Models\NewsEntry;
use App\Models\NewsItem;
use App\Models\Preference;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\Notifications\NovedadNotifier;
use App\Services\Notifications\WebPushSender;
use App\Support\WebPushPayload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Minishlink\WebPush\MessageSentReport;
use Minishlink\WebPush\WebPush;

uses(RefreshDatabase::class);

function makePushItem(MonitoredTopic $topic, int $relevance = 80): NewsItem
{
    $entry = NewsEntry::factory()->create(['relevance_score' => $relevance]);

    return NewsItem::factory()->for($topic, 'monitoredTopic')->for($topic->user)->create([
        'news_entry_id' => $entry->id,
        'novedad_status' => NewsItemStatus::New,
        'is_irrelevant' => false,
        'relevance' => $relevance,
    ]);
}

test('an eligible novedad queues a push per active device', function () {
    Queue::fake();

    $user = User::factory()->create();
    $topic = MonitoredTopic::factory()->for($user)->create([
        'notify_push' => true,
        'notify_email' => false,
    ]);
    $item = makePushItem($topic);

    PushSubscription::factory()->for($user)->create();
    PushSubscription::factory()->for($user)->create();
    PushSubscription::factory()->for($user)->expired()->create();

    $queued = app(NovedadNotifier::class)->dispatchPushAlert($item);

    expect($queued)->toBe(2);

    Queue::assertPushed(SendWebPushNotificationJob::class, 2);
});

test('push is skipped when the topic disables it', function () {
    Queue::fake();

    $user = User::factory()->create();
    $topic = MonitoredTopic::factory()->for($user)->create(['notify_push' => false]);
    $item = makePushItem($topic);

    PushSubscription::factory()->for($user)->create();

    expect(app(NovedadNotifier::class)->dispatchPushAlert($item))->toBe(0);

    Queue::assertNothingPushed();
});

test('push is skipped when the user disables it globally', function () {
    Queue::fake();

    $user = User::factory()->create();
    Preference::factory()->for($user)->create(['push_notifications' => false]);
    $topic = MonitoredTopic::factory()->for($user)->create(['notify_push' => true]);
    $item = makePushItem($topic);

    PushSubscription::factory()->for($user)->create();

    expect(app(NovedadNotifier::class)->dispatchPushAlert($item))->toBe(0);

    Queue::assertNothingPushed();
});

test('push respects the relevance floor', function () {
    Queue::fake();

    $user = User::factory()->create();
    $topic = MonitoredTopic::factory()->for($user)->create([
        'notify_push' => true,
        'relevance_preferences' => ['notify_threshold' => 90],
    ]);
    $item = makePushItem($topic, 40);

    PushSubscription::factory()->for($user)->create();

    expect(app(NovedadNotifier::class)->dispatchPushAlert($item))->toBe(0);

    Queue::assertNothingPushed();
});

test('push payload hides the headline under privacy mode', function () {
    $user = User::factory()->create();
    $preference = Preference::factory()->for($user)->create(['push_private' => true]);
    $topic = MonitoredTopic::factory()->for($user)->create(['notify_push' => true]);
    $item = makePushItem($topic);

    $payload = WebPushPayload::forNovedad($item, $preference);

    expect($payload['title'])->toBe(config('webpush.generic_title'))
        ->and($payload['body'])->toBe(config('webpush.generic_body'))
        ->and($payload['private'])->toBeTrue()
        ->and($payload['title'])->not->toContain($item->title);
});

test('push payload shows the headline when privacy is off', function () {
    $user = User::factory()->create();
    $preference = Preference::factory()->for($user)->create(['push_private' => false]);
    $topic = MonitoredTopic::factory()->for($user)->create(['notify_push' => true]);
    $item = makePushItem($topic);

    $payload = WebPushPayload::forNovedad($item, $preference);

    expect($payload['title'])->toBe($item->title)
        ->and($payload['private'])->toBeFalse();
});

test('a browser registers a push subscription', function () {
    $user = User::factory()->create();

    $endpoint = 'https://fcm.googleapis.com/fcm/send/'.fake()->uuid();

    $response = $this->actingAs($user)->postJson(route('push.subscriptions.store'), [
        'endpoint' => $endpoint,
        'keys' => ['p256dh' => 'pkey', 'auth' => 'atoken'],
        'contentEncoding' => 'aesgcm',
        'device_label' => 'Chrome',
    ]);

    $response->assertCreated()->assertJson(['active' => true]);

    $this->assertDatabaseHas('push_subscriptions', [
        'user_id' => $user->id,
        'endpoint_hash' => PushSubscription::hashEndpoint($endpoint),
    ]);
});

test('re-registering the same endpoint updates instead of duplicating', function () {
    $user = User::factory()->create();
    $endpoint = 'https://fcm.googleapis.com/fcm/send/same';

    $payload = [
        'endpoint' => $endpoint,
        'keys' => ['p256dh' => 'pkey', 'auth' => 'atoken'],
    ];

    $this->actingAs($user)->postJson(route('push.subscriptions.store'), $payload)->assertCreated();
    $this->actingAs($user)->postJson(route('push.subscriptions.store'), $payload)->assertCreated();

    expect(PushSubscription::query()->where('user_id', $user->id)->count())->toBe(1);
});

test('a user cannot delete another users subscription', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $subscription = PushSubscription::factory()->for($owner)->create();

    $this->actingAs($other)
        ->delete(route('push.subscriptions.destroy', $subscription))
        ->assertForbidden();

    expect($subscription->fresh())->not->toBeNull();
});

test('suspended users cannot register push subscriptions', function () {
    $user = User::factory()->create(['status' => 'suspended']);

    $this->actingAs($user)->postJson(route('push.subscriptions.store'), [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/x',
        'keys' => ['p256dh' => 'pkey', 'auth' => 'atoken'],
    ])->assertForbidden();
});

test('the push test page lists the user devices', function () {
    $user = User::factory()->create();
    PushSubscription::factory()->for($user)->create(['device_label' => 'Firefox']);

    $this->actingAs($user)
        ->get(route('push.test'))
        ->assertOk()
        ->assertSee('Firefox');
});

test('the test notification is queued for every active device', function () {
    Queue::fake();

    $user = User::factory()->create();
    PushSubscription::factory()->for($user)->create();
    PushSubscription::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('push.test.send'))
        ->assertRedirect();

    Queue::assertPushed(SendWebPushNotificationJob::class, 2);
});

test('the sender marks expired subscriptions from a gone response', function () {
    $user = User::factory()->create();
    $subscription = PushSubscription::factory()->for($user)->create();

    $report = Mockery::mock(MessageSentReport::class);
    $report->shouldReceive('isSuccess')->andReturn(false);
    $report->shouldReceive('isSubscriptionExpired')->andReturn(true);

    $webPush = Mockery::mock(WebPush::class);
    $webPush->shouldReceive('sendOneNotification')->once()->andReturn($report);

    $sender = Mockery::mock(WebPushSender::class)->makePartial();
    $sender->shouldAllowMockingProtectedMethods();
    $sender->shouldReceive('client')->andReturn($webPush);
    $sender->shouldReceive('isConfigured')->andReturn(true);

    $sent = $sender->send([$subscription], ['title' => 'x']);

    expect($sent)->toBe(0)
        ->and($subscription->fresh()->expired_at)->not->toBeNull();
});
