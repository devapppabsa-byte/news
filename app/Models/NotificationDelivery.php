<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use App\Enums\NotificationMode;
use Database\Factories\NotificationDeliveryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationDelivery extends Model
{
    /** @use HasFactory<NotificationDeliveryFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'monitored_topic_id',
        'news_entry_id',
        'channel',
        'mode',
        'dedupe_key',
        'status',
        'attempts',
        'items_count',
        'period_start',
        'error_message',
        'queued_at',
        'sent_at',
        'failed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mode' => NotificationMode::class,
            'status' => DeliveryStatus::class,
            'attempts' => 'integer',
            'items_count' => 'integer',
            'period_start' => 'datetime',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<NotificationDelivery>  $query
     */
    public function scopeSent(Builder $query): void
    {
        $query->where('status', DeliveryStatus::Sent);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<MonitoredTopic, $this>
     */
    public function monitoredTopic(): BelongsTo
    {
        return $this->belongsTo(MonitoredTopic::class);
    }

    /**
     * @return BelongsTo<NewsEntry, $this>
     */
    public function newsEntry(): BelongsTo
    {
        return $this->belongsTo(NewsEntry::class);
    }

    public function markSent(): void
    {
        $this->forceFill([
            'status' => DeliveryStatus::Sent,
            'sent_at' => now(),
            'error_message' => null,
        ])->save();
    }

    public function markFailed(string $message): void
    {
        $this->forceFill([
            'status' => DeliveryStatus::Failed,
            'failed_at' => now(),
            'error_message' => mb_substr($message, 0, 500),
        ])->save();
    }

    public function markSkipped(string $reason): void
    {
        $this->forceFill([
            'status' => DeliveryStatus::Skipped,
            'error_message' => mb_substr($reason, 0, 500),
        ])->save();
    }
}
