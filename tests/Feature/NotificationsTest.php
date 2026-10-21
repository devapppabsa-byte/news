<?php

use App\Enums\AccountStatus;
use App\Enums\DeliveryStatus;
use App\Enums\NewsItemStatus;
use App\Enums\NotificationMode;
use App\Jobs\SendNovedadAlertJob;
use App\Jobs\SendNovedadDigestJob;
use App\Mail\NovedadAlert;
use App\Mail\NovedadDigest as NovedadDigestMailable;
use App\Models\MonitoredTopic;
use App\Models\NewsEntry;
use App\Models\NewsItem;
use App\Models\NotificationDelivery;
use App\Models\Preference;
use App\Models\User;
use App\Services\Notifications\MailFromResolver;
use App\Services\Notifications\NovedadDigest;
use App\Services\Notifications\NovedadNotifier;
use App\Support\NotificationKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function makeNotifiableTopic(User $user, array $topicAttributes = [], array $preferenceAttributes = []): MonitoredTopic
{
    Preference::query()->firstOrCreate(
        ['user_id' => $user->id],
        Preference::factory()->for($user)->make($preferenceAttributes)->toArray(),
    );

    return MonitoredTopic::factory()->for($user)->create(array_merge([
        'notify_email' => true,
        'notification_mode' => NotificationMode::Individual,
    ], $topicAttributes));
}

function makeNewItem(MonitoredTopic $topic, int $relevance = 80, array $attributes = []): NewsItem
{
    $entry = NewsEntry::factory()->create(['relevance_score' => $relevance]);

    return NewsItem::factory()->for($topic, 'monitoredTopic')->for($topic->user)->create(array_merge([
        'news_entry_id' => $entry->id,
        'novedad_status' => NewsItemStatus::New,
        'is_irrelevant' => false,
        'relevance' => $relevance,
    ], $attributes));
}

test('an eligible novedad queues an individual alert', function () {
    Queue::fake();

    $user = User::factory()->create();
    $topic = makeNotifiableTopic($user);
    $item = makeNewItem($topic);

    $delivery = app(NovedadNotifier::class)->dispatchIndividualAlert($item);

    expect($delivery)->not->toBeNull()
        ->and($delivery->status)->toBe(DeliveryStatus::Queued)
        ->and($delivery->mode)->toBe(NotificationMode::Individual);

    Queue::assertPushed(SendNovedadAlertJob::class);
});

test('a second alert for the same entry is deduplicated', function () {
    Queue::fake();

    $user = User::factory()->create();
    $topic = makeNotifiableTopic($user);
    $item = makeNewItem($topic);

    $notifier = app(NovedadNotifier::class);

    $first = $notifier->dispatchIndividualAlert($item);
    $second = $notifier->dispatchIndividualAlert($item->fresh());

    expect($first)->not->toBeNull()
        ->and($second)->toBeNull()
        ->and(NotificationDelivery::query()->count())->toBe(1);
});

test('the global preference switch disables all alerts', function () {
    Queue::fake();

    $user = User::factory()->create();
    $topic = makeNotifiableTopic($user, [], ['email_notifications' => false]);
    $item = makeNewItem($topic);

    $delivery = app(NovedadNotifier::class)->dispatchIndividualAlert($item);

    expect($delivery)->toBeNull();
    Queue::assertNothingPushed();
});

test('the topic email switch disables alerts', function () {
    Queue::fake();

    $user = User::factory()->create();
    $topic = makeNotifiableTopic($user, ['notify_email' => false]);
    $item = makeNewItem($topic);

    expect(app(NovedadNotifier::class)->dispatchIndividualAlert($item))->toBeNull();
});

test('a digest-only topic does not queue an individual alert', function () {
    Queue::fake();

    $user = User::factory()->create();
    $topic = makeNotifiableTopic($user, ['notification_mode' => NotificationMode::Digest]);
    $item = makeNewItem($topic);

    expect(app(NovedadNotifier::class)->dispatchIndividualAlert($item))->toBeNull();
    Queue::assertNothingPushed();
});

test('irrelevant items and low relevance values are excluded', function () {
    Queue::fake();

    $user = User::factory()->create();
    $topic = makeNotifiableTopic($user, [
        'relevance_preferences' => ['min_relevance' => 0, 'sort' => 'date', 'notify_threshold' => 50],
    ]);

    $low = makeNewItem($topic, relevance: 10);
    $flagged = makeNewItem($topic, relevance: 90, attributes: ['is_irrelevant' => true]);

    $notifier = app(NovedadNotifier::class);

    expect($notifier->dispatchIndividualAlert($low))->toBeNull()
        ->and($notifier->dispatchIndividualAlert($flagged))->toBeNull();
});

test('a suspended user never receives an alert', function () {
    Queue::fake();

    $user = User::factory()->create();
    $topic = makeNotifiableTopic($user);
    $item = makeNewItem($topic);

    $user->forceFill(['status' => AccountStatus::Suspended])->save();

    expect(app(NovedadNotifier::class)->dispatchIndividualAlert($item))->toBeNull();
});

test('the daily limit stops further individual alerts', function () {
    Queue::fake();
    config()->set('notifications.limits.max_per_day', 1);

    $user = User::factory()->create();
    $topic = makeNotifiableTopic($user);

    NotificationDelivery::factory()->for($user)->sent()->create([
        'mode' => NotificationMode::Individual,
        'sent_at' => now(),
    ]);

    $item = makeNewItem($topic);

    expect(app(NovedadNotifier::class)->dispatchIndividualAlert($item))->toBeNull();
});

test('the alert job sends the mailable and marks the delivery sent', function () {
    Mail::fake();

    $user = User::factory()->create();
    $topic = makeNotifiableTopic($user);
    $item = makeNewItem($topic);

    $delivery = NotificationDelivery::factory()->for($user)->create([
        'monitored_topic_id' => $topic->id,
        'news_entry_id' => $item->news_entry_id,
        'mode' => NotificationMode::Individual,
        'dedupe_key' => NotificationKey::individual($user->id, $item->news_entry_id),
    ]);

    (new SendNovedadAlertJob($delivery->id))->handle(app(NovedadNotifier::class), app(MailFromResolver::class));

    Mail::assertSent(NovedadAlert::class);
    expect($delivery->fresh()->status)->toBe(DeliveryStatus::Sent)
        ->and($delivery->fresh()->sent_at)->not->toBeNull();
});

test('the alert job skips when the recipient is no longer eligible', function () {
    Mail::fake();

    $user = User::factory()->create();
    $topic = makeNotifiableTopic($user, ['notify_email' => false]);
    $item = makeNewItem($topic);

    $delivery = NotificationDelivery::factory()->for($user)->create([
        'monitored_topic_id' => $topic->id,
        'news_entry_id' => $item->news_entry_id,
        'dedupe_key' => NotificationKey::individual($user->id, $item->news_entry_id),
    ]);

    (new SendNovedadAlertJob($delivery->id))->handle(app(NovedadNotifier::class), app(MailFromResolver::class));

    Mail::assertNothingSent();
    expect($delivery->fresh()->status)->toBe(DeliveryStatus::Skipped);
});

test('a digest is queued once per user and period', function () {
    Queue::fake();

    $user = User::factory()->create();
    $topic = makeNotifiableTopic($user, ['notification_mode' => NotificationMode::Digest]);
    makeNewItem($topic, relevance: 70);

    $digest = app(NovedadDigest::class);

    expect($digest->queueFor(now()->subDay()))->toBe(1)
        ->and($digest->queueFor(now()->subDay()))->toBe(0)
        ->and(NotificationDelivery::query()->count())->toBe(1);

    Queue::assertPushed(SendNovedadDigestJob::class);
});

test('the digest job sends the summary and marks it sent', function () {
    Mail::fake();

    $user = User::factory()->create();
    $topic = makeNotifiableTopic($user, ['notification_mode' => NotificationMode::Digest]);
    makeNewItem($topic, relevance: 70);

    $delivery = NotificationDelivery::factory()->for($user)->digest()->create([
        'monitored_topic_id' => null,
        'period_start' => now()->subDay(),
        'dedupe_key' => NotificationKey::digest($user->id, NotificationKey::periodFor()),
    ]);

    (new SendNovedadDigestJob($delivery->id))->handle(app(NovedadDigest::class), app(MailFromResolver::class));

    Mail::assertSent(NovedadDigestMailable::class);
    expect($delivery->fresh()->status)->toBe(DeliveryStatus::Sent);
});

test('the digest job skips when there is nothing to summarise', function () {
    Mail::fake();

    $user = User::factory()->create();
    makeNotifiableTopic($user, ['notification_mode' => NotificationMode::Digest]);

    $delivery = NotificationDelivery::factory()->for($user)->digest()->create([
        'monitored_topic_id' => null,
        'period_start' => now()->subDay(),
        'dedupe_key' => NotificationKey::digest($user->id, NotificationKey::periodFor()),
    ]);

    (new SendNovedadDigestJob($delivery->id))->handle(app(NovedadDigest::class), app(MailFromResolver::class));

    Mail::assertNothingSent();
    expect($delivery->fresh()->status)->toBe(DeliveryStatus::Skipped);
});

test('the failed hook records a sanitized failure', function () {
    $user = User::factory()->create();
    $delivery = NotificationDelivery::factory()->for($user)->create();

    (new SendNovedadAlertJob($delivery->id))->failed(new RuntimeException('boom sk-or-abc123'));

    $fresh = $delivery->fresh();

    expect($fresh->status)->toBe(DeliveryStatus::Failed)
        ->and($fresh->error_message)->not->toContain('sk-or-abc123');
});

test('a user can update their global notification preferences', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('preferences.update'), [
            'locale' => 'en',
            'timezone' => 'Europe/Madrid',
            'email_notifications' => '1',
            'notification_mode' => NotificationMode::Both->value,
            'digest_frequency' => 'weekly',
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));

    $preference = Preference::query()->where('user_id', $user->id)->first();

    expect($preference)->not->toBeNull()
        ->and($preference->notification_mode)->toBe(NotificationMode::Both)
        ->and($preference->digest_frequency)->toBe('weekly')
        ->and($preference->locale)->toBe('en');
});
