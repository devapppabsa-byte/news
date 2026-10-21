<?php

namespace App\Services\Notifications;

use App\Enums\NewsItemStatus;
use App\Enums\NotificationMode;
use App\Jobs\SendNovedadDigestJob;
use App\Models\MonitoredTopic;
use App\Models\NewsItem;
use App\Models\Preference;
use App\Services\Settings\SettingsRepository;
use App\Support\NotificationKey;
use App\Support\SettingKeys;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class NovedadDigest
{
    public function __construct(
        protected NovedadNotifier $notifier,
        protected SettingsRepository $settings,
    ) {}

    /**
     * Build and queue a digest for every active user that wants one. Returns the
     * number of digests queued.
     *
     * @param  Carbon|null  $since  Only novedades discovered after this moment.
     */
    public function queueFor(?Carbon $since = null): int
    {
        $since ??= now()->subDay();

        $queued = 0;

        $preferences = Preference::query()
            ->with('user')
            ->where('email_notifications', true)
            ->get();

        foreach ($preferences as $preference) {
            if ($this->queueForUser($preference, $since)) {
                $queued++;
            }
        }

        return $queued;
    }

    /**
     * Queue a digest for a single user when they have notifiable novedades.
     * Returns true when a digest was reserved.
     */
    public function queueForUser(Preference $preference, ?Carbon $since = null): bool
    {
        $since ??= now()->subDay();
        $user = $preference->user;

        if ($user === null || $user->isSuspended()) {
            return false;
        }

        if (! $preference->wantsEmailNotifications()) {
            return false;
        }

        $items = $this->collectItems($user->id, $since);

        if ($items->isEmpty()) {
            return false;
        }

        $topics = $user->monitoredTopics()->get()->keyBy('id');

        $digestItems = $this->format($items, $topics);

        if ($digestItems->isEmpty()) {
            return false;
        }

        $period = NotificationKey::periodFor();

        $delivery = $this->notifier->reserve(
            userId: $user->id,
            topic: null,
            newsEntryId: null,
            mode: NotificationMode::Digest,
            key: NotificationKey::digest($user->id, $period),
            itemsCount: $digestItems->count(),
            periodStart: $since,
        );

        if ($delivery === null) {
            return false;
        }

        SendNovedadDigestJob::dispatch($delivery->id);

        return true;
    }

    /**
     * @return Collection<int, NewsItem>
     */
    protected function collectItems(int $userId, Carbon $since): Collection
    {
        return NewsItem::query()
            ->with('monitoredTopic')
            ->where('user_id', $userId)
            ->where('novedad_status', NewsItemStatus::New)
            ->where('is_irrelevant', false)
            ->where('created_at', '>=', $since)
            ->orderByDesc('relevance')
            ->orderByDesc('created_at')
            ->limit($this->settings->integer(SettingKeys::DIGEST_MAX_ITEMS))
            ->get();
    }

    /**
     * Rebuild the formatted digest items for a user within a period window.
     * Used by the delivery job so the message reflects the reserved window.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function itemsForUser(int $userId, Carbon $since): Collection
    {
        $topics = MonitoredTopic::query()
            ->where('user_id', $userId)
            ->get()
            ->keyBy('id');

        return $this->format($this->collectItems($userId, $since), $topics);
    }

    /**
     * @param  Collection<int, NewsItem>  $items
     * @param  Collection<int, MonitoredTopic>  $topics
     * @return Collection<int, array<string, mixed>>
     */
    protected function format(Collection $items, Collection $topics): Collection
    {
        return $items
            ->filter(function (NewsItem $item) use ($topics) {
                $topic = $topics->get($item->topic_id);

                if ($topic === null) {
                    return false;
                }

                $mode = $topic->effectiveNotificationMode();

                if (! $mode->sendsDigest()) {
                    return false;
                }

                return $this->notifier->meetsRelevance($topic, $item);
            })
            ->map(fn (NewsItem $item) => [
                'title' => $item->title,
                'url' => $item->url,
                'summary' => $item->body ?: $item->ai_summary,
                'source' => $item->source_domain,
                'topic' => $item->monitoredTopic?->name,
                'relevance' => $item->relevance,
            ])
            ->values();
    }
}
