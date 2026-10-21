<?php

namespace App\Models;

use App\Enums\NewsItemStatus;
use Database\Factories\NewsEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsEntry extends Model
{
    /** @use HasFactory<NewsEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'url',
        'normalized_url',
        'url_hash',
        'content_hash',
        'source_name',
        'source_domain',
        'published_at',
        'discovered_at',
        'summary',
        'relevance_score',
        'source_available',
        'source_checked_at',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'discovered_at' => 'datetime',
            'source_checked_at' => 'datetime',
            'source_available' => 'boolean',
            'relevance_score' => 'integer',
            'status' => NewsItemStatus::class,
        ];
    }

    /**
     * @return HasMany<NewsItem, $this>
     */
    public function newsItems(): HasMany
    {
        return $this->hasMany(NewsItem::class);
    }
}
