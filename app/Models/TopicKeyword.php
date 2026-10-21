<?php

namespace App\Models;

use App\Enums\KeywordType;
use Database\Factories\TopicKeywordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopicKeyword extends Model
{
    /** @use HasFactory<TopicKeywordFactory> */
    use HasFactory;

    protected $fillable = ['monitored_topic_id', 'type', 'value'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => KeywordType::class,
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
