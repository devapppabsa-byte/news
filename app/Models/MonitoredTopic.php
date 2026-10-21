<?php

namespace App\Models;

use App\Enums\KeywordType;
use App\Enums\NotificationMode;
use App\Enums\RunStatus;
use App\Enums\SearchFrequency;
use App\Enums\SourceType;
use App\Enums\TopicStatus;
use App\Support\RelevancePreferences;
use Carbon\Carbon;
use Database\Factories\MonitoredTopicFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoredTopic extends Model
{
    /** @use HasFactory<MonitoredTopicFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'language',
        'country',
        'frequency',
        'custom_interval_minutes',
        'status',
        'last_run_at',
        'next_run_at',
        'notify_email',
        'notify_push',
        'notification_mode',
        'relevance_preferences',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'frequency' => SearchFrequency::class,
            'status' => TopicStatus::class,
            'last_run_at' => 'datetime',
            'next_run_at' => 'datetime',
            'notify_email' => 'boolean',
            'notify_push' => 'boolean',
            'notification_mode' => NotificationMode::class,
            'relevance_preferences' => RelevancePreferences::class,
        ];
    }

    /**
     * @param  Builder<MonitoredTopic>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', TopicStatus::Active);
    }

    /**
     * @param  Builder<MonitoredTopic>  $query
     */
    public function scopeDue(Builder $query): void
    {
        $query->active()
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now());
    }

    /**
     * @param  Builder<MonitoredTopic>  $query
     */
    public function scopeForUser(Builder $query, User $user): void
    {
        $query->where('user_id', $user->id);
    }

    public function isActive(): bool
    {
        return $this->status === TopicStatus::Active;
    }

    public function isPaused(): bool
    {
        return $this->status === TopicStatus::Paused;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<TopicKeyword, $this>
     */
    public function keywords(): HasMany
    {
        return $this->hasMany(TopicKeyword::class);
    }

    /**
     * @return HasMany<TopicKeyword, $this>
     */
    public function includedKeywords(): HasMany
    {
        return $this->keywords()->where('type', KeywordType::Include);
    }

    /**
     * @return HasMany<TopicKeyword, $this>
     */
    public function excludedKeywords(): HasMany
    {
        return $this->keywords()->where('type', KeywordType::Exclude);
    }

    /**
     * @return HasMany<TopicSource, $this>
     */
    public function sources(): HasMany
    {
        return $this->hasMany(TopicSource::class);
    }

    /**
     * @return HasMany<TopicSource, $this>
     */
    public function preferredSources(): HasMany
    {
        return $this->sources()->where('type', SourceType::Preferred);
    }

    /**
     * @return HasMany<TopicSource, $this>
     */
    public function excludedSources(): HasMany
    {
        return $this->sources()->where('type', SourceType::Excluded);
    }

    /**
     * @return HasMany<TopicRun, $this>
     */
    public function runs(): HasMany
    {
        return $this->hasMany(TopicRun::class);
    }

    /**
     * @return HasMany<TopicRun, $this>
     */
    public function latestRun(): HasMany
    {
        return $this->runs()->latest('created_at');
    }

    /**
     * @return HasMany<NewsItem, $this>
     */
    public function newsItems(): HasMany
    {
        return $this->hasMany(NewsItem::class, 'topic_id');
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'topic_id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'subscriptions', 'topic_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Recalculate the next scheduled run based on the current frequency.
     */
    public function scheduleNextRun(?\DateTimeInterface $from = null): void
    {
        $from = $from ? \Illuminate\Support\Carbon::instance(
            Carbon::parse($from)
        ) : now();

        $this->next_run_at = $this->frequency->nextRunFrom(
            $from,
            $this->custom_interval_minutes,
        );
    }

    public function hasSuccessfulRun(): bool
    {
        return $this->runs()->where('status', RunStatus::Success)->exists();
    }

    /**
     * @return HasMany<NotificationDelivery, $this>
     */
    public function notificationDeliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class);
    }

    /**
     * The effective notification mode combining topic, user and global
     * preferences. The most restrictive source wins: the global master switch,
     * then the user preference, then the topic override.
     *
     * @param  Preference|null  $preference  The owning user's preference.
     */
    public function effectiveNotificationMode(?Preference $preference = null): NotificationMode
    {
        if (! (bool) config('notifications.enabled', true)) {
            return NotificationMode::None;
        }

        if (! $this->notify_email) {
            return NotificationMode::None;
        }

        if ($preference !== null && ! $preference->wantsEmailNotifications()) {
            return NotificationMode::None;
        }

        if ($this->notification_mode !== null) {
            return $this->notification_mode;
        }

        if ($preference !== null) {
            return $preference->notificationMode();
        }

        return NotificationMode::from((string) config('notifications.default_mode', 'digest'));
    }
}
