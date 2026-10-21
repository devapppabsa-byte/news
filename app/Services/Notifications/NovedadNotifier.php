<?php

namespace App\Services\Notifications;

use App\Enums\DeliveryStatus;
use App\Enums\NotificationMode;
use App\Jobs\SendNovedadAlertJob;
use App\Jobs\SendWebPushNotificationJob;
use App\Models\MonitoredTopic;
use App\Models\NewsItem;
use App\Models\NotificationDelivery;
use App\Models\Preference;
use App\Models\PushSubscription;
use App\Services\Settings\SettingsRepository;
use App\Support\NotificationKey;
use App\Support\SettingKeys;
use App\Support\WebPushPayload;
use Illuminate\Support\Carbon;

class NovedadNotifier
{
    public function __construct(
        protected SettingsRepository $settings,
    ) {}

    /**
     * Evaluate a freshly created news item and queue an immediate alert when the
     * user, topic and relevance rules allow it. Delivery is deduplicated by a
     * unique key so a retried pipeline never sends the same alert twice.
     */
    public function dispatchIndividualAlert(NewsItem $item): ?NotificationDelivery
    {
        if ($item->news_entry_id === null) {
            return null;
        }

        $topic = $item->monitoredTopic;

        if ($topic === null) {
            return null;
        }

        $preference = $this->preferenceFor($topic);

        if (! $this->isEligible($topic, $item, $preference)) {
            return null;
        }

        $mode = $topic->effectiveNotificationMode($preference);

        if (! $mode->sendsIndividual()) {
            return null;
        }

        if (! $this->withinDailyLimit($topic->user_id)) {
            return null;
        }

        $delivery = $this->reserve(
            userId: $topic->user_id,
            topic: $topic,
            newsEntryId: $item->news_entry_id,
            mode: NotificationMode::Individual,
            key: NotificationKey::individual($topic->user_id, $item->news_entry_id),
            itemsCount: 1,
        );

        if ($delivery === null) {
            return null;
        }

        SendNovedadAlertJob::dispatch($delivery->id);

        return $delivery;
    }

    /**
     * Queue a web push alert for every active browser subscription of the
     * topic owner. Eligibility follows the topic "notify_push" flag, the global
     * push preference and the relevance floor. Returns the number of browsers
     * the alert was queued for.
     */
    public function dispatchPushAlert(NewsItem $item): int
    {
        if ($item->news_entry_id === null) {
            return 0;
        }

        $topic = $item->monitoredTopic;

        if ($topic === null || ! $this->pushEligible($topic, $item)) {
            return 0;
        }

        $subscriptions = PushSubscription::query()
            ->active()
            ->where('user_id', $topic->user_id)
            ->get();

        if ($subscriptions->isEmpty()) {
            return 0;
        }

        $preference = $this->preferenceFor($topic);
        $payload = WebPushPayload::forNovedad($item, $preference);

        foreach ($subscriptions as $subscription) {
            SendWebPushNotificationJob::dispatch($subscription->id, $payload);
        }

        return $subscriptions->count();
    }

    /**
     * Determine whether a novedad should reach the browser.
     */
    public function pushEligible(MonitoredTopic $topic, NewsItem $item): bool
    {
        if (! (bool) config('webpush.enabled', true)) {
            return false;
        }

        $user = $topic->user;

        if ($user === null || $user->isSuspended()) {
            return false;
        }

        if (! $topic->notify_push) {
            return false;
        }

        $preference = $this->preferenceFor($topic);

        if ($preference !== null && ! $preference->wantsPushNotifications()) {
            return false;
        }

        if ($item->is_irrelevant) {
            return false;
        }

        return $this->meetsRelevance($topic, $item);
    }

    /**
     * Determine whether a novedad should trigger any email at all.
     */
    public function isEligible(MonitoredTopic $topic, NewsItem $item, ?Preference $preference = null): bool
    {
        if (! $this->settings->boolean(SettingKeys::NOTIFICATIONS_ENABLED)) {
            return false;
        }

        $user = $topic->user;

        if ($user === null || $user->isSuspended()) {
            return false;
        }

        $preference ??= $this->preferenceFor($topic);

        if (! $topic->notify_email) {
            return false;
        }

        if ($preference !== null && ! $preference->wantsEmailNotifications()) {
            return false;
        }

        if ($item->is_irrelevant) {
            return false;
        }

        return $this->meetsRelevance($topic, $item);
    }

    /**
     * The relevance floor is the stricter of the global default and the topic's
     * own notify threshold.
     */
    public function meetsRelevance(MonitoredTopic $topic, NewsItem $item): bool
    {
        $globalFloor = $this->settings->integer(SettingKeys::NOTIFICATIONS_MIN_RELEVANCE);
        $topicFloor = $topic->relevance_preferences?->notifyThreshold ?? 0;
        $floor = max($globalFloor, $topicFloor);

        return ($item->relevance ?? 0) >= $floor;
    }

    /**
     * Count today's sent individual alerts for a user.
     */
    public function sentTodayCount(int $userId): int
    {
        return NotificationDelivery::query()
            ->where('user_id', $userId)
            ->where('mode', NotificationMode::Individual)
            ->where('status', DeliveryStatus::Sent)
            ->where('sent_at', '>=', Carbon::today())
            ->count();
    }

    public function withinDailyLimit(int $userId): bool
    {
        $limit = $this->settings->integer(SettingKeys::NOTIFICATIONS_MAX_PER_DAY);

        if ($limit <= 0) {
            return true;
        }

        return $this->sentTodayCount($userId) < $limit;
    }

    /**
     * Reserve a delivery row, returning null when the dedupe key already exists.
     * A unique constraint on dedupe_key makes this safe under concurrency.
     */
    public function reserve(
        int $userId,
        ?MonitoredTopic $topic,
        ?int $newsEntryId,
        NotificationMode $mode,
        string $key,
        int $itemsCount = 1,
        ?\DateTimeInterface $periodStart = null,
    ): ?NotificationDelivery {
        if (NotificationDelivery::query()->where('dedupe_key', $key)->exists()) {
            return null;
        }

        return NotificationDelivery::query()->create([
            'user_id' => $userId,
            'monitored_topic_id' => $topic?->id,
            'news_entry_id' => $newsEntryId,
            'channel' => 'mail',
            'mode' => $mode,
            'dedupe_key' => $key,
            'status' => DeliveryStatus::Queued,
            'items_count' => $itemsCount,
            'period_start' => $periodStart,
            'queued_at' => now(),
        ]);
    }

    protected function preferenceFor(MonitoredTopic $topic): ?Preference
    {
        return Preference::query()->where('user_id', $topic->user_id)->first();
    }
}
