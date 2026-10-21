<?php

namespace App\Models;

use App\Enums\SourceType;
use Database\Factories\TopicSourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopicSource extends Model
{
    /** @use HasFactory<TopicSourceFactory> */
    use HasFactory;

    protected $fillable = ['monitored_topic_id', 'type', 'domain'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => SourceType::class,
        ];
    }

    /**
     * @return BelongsTo<MonitoredTopic, $this>
     */
    public function monitoredTopic(): BelongsTo
    {
        return $this->belongsTo(MonitoredTopic::class);
    }
}
