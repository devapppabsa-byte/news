<?php

namespace App\Models;

use App\Enums\NewsItemStatus;
use Database\Factories\NewsItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsItem extends Model
{
    /** @use HasFactory<NewsItemFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'topic_id',
        'topic_run_id',
        'news_entry_id',
        'title',
        'slug',
        'body',
        'ai_summary',
        'ai_relevance',
        'ai_duplicate_of_id',
        'ai_analyzed_at',
        'url',
        'source_domain',
        'author',
        'relevance',
        'collected_at',
        'source_identifier',
        'status',
        'novedad_status',
        'is_read',
        'read_at',
        'is_saved',
        'saved_at',
        'is_irrelevant',
        'irrelevant_at',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'collected_at' => 'datetime',
            'ai_analyzed_at' => 'datetime',
            'read_at' => 'datetime',
            'saved_at' => 'datetime',
            'irrelevant_at' => 'datetime',
            'relevance' => 'integer',
            'ai_relevance' => 'integer',
            'novedad_status' => NewsItemStatus::class,
            'is_read' => 'boolean',
            'is_saved' => 'boolean',
            'is_irrelevant' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<NewsEntry, $this>
     */
    public function newsEntry(): BelongsTo
    {
        return $this->belongsTo(NewsEntry::class);
    }

    /**
     * @return BelongsTo<MonitoredTopic, $this>
     */
    public function monitoredTopic(): BelongsTo
    {
        return $this->belongsTo(MonitoredTopic::class, 'topic_id');
    }

    /**
     * @return BelongsTo<TopicRun, $this>
     */
    public function topicRun(): BelongsTo
    {
        return $this->belongsTo(TopicRun::class);
    }

    /**
     * @return BelongsTo<NewsItem, $this>
     */
    public function aiDuplicateOf(): BelongsTo
    {
        return $this->belongsTo(NewsItem::class, 'ai_duplicate_of_id');
    }

    public function markRead(bool $read = true): void
    {
        $this->forceFill([
            'is_read' => $read,
            'read_at' => $read ? now() : null,
        ])->save();
    }

    public function markSaved(bool $saved = true): void
    {
        $this->forceFill([
            'is_saved' => $saved,
            'saved_at' => $saved ? now() : null,
        ])->save();
    }

    public function markIrrelevant(bool $irrelevant = true): void
    {
        $this->forceFill([
            'is_irrelevant' => $irrelevant,
            'irrelevant_at' => $irrelevant ? now() : null,
        ])->save();
    }
}
