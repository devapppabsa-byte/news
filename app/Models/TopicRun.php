<?php

namespace App\Models;

use App\Enums\RunStatus;
use App\Enums\RunTrigger;
use Database\Factories\TopicRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TopicRun extends Model
{
    /** @use HasFactory<TopicRunFactory> */
    use HasFactory;

    protected $fillable = [
        'monitored_topic_id',
        'trigger',
        'status',
        'results_count',
        'error_message',
        'started_at',
        'finished_at',
        'queued_at',
        'duration_ms',
        'attempts',
        'estimated_cost',
        'provider_usage',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trigger' => RunTrigger::class,
            'status' => RunStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'queued_at' => 'datetime',
            'duration_ms' => 'integer',
            'attempts' => 'integer',
            'estimated_cost' => 'decimal:6',
            'provider_usage' => 'array',
        ];
    }

    /**
     * @return BelongsTo<MonitoredTopic, $this>
     */
    public function monitoredTopic(): BelongsTo
    {
        return $this->belongsTo(MonitoredTopic::class);
    }

    /**
     * @return HasMany<NewsItem, $this>
     */
    public function newsItems(): HasMany
    {
        return $this->hasMany(NewsItem::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === RunStatus::Success;
    }

    public function durationInSeconds(): ?int
    {
        if ($this->started_at === null || $this->finished_at === null) {
            return null;
        }

        return $this->started_at->diffInSeconds($this->finished_at);
    }
}
