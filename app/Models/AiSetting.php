<?php

namespace App\Models;

use App\Enums\AISearchMode;
use Database\Factories\AiSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiSetting extends Model
{
    /** @use HasFactory<AiSettingFactory> */
    use HasFactory;

    protected $fillable = [
        'provider',
        'model',
        'search_mode',
        'parameters',
        'max_tokens',
        'timeout',
        'fallback_provider',
        'fallback_model',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'search_mode' => AISearchMode::class,
            'parameters' => 'array',
            'max_tokens' => 'integer',
            'timeout' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
