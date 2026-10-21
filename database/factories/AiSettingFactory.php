<?php

namespace Database\Factories;

use App\Enums\AISearchMode;
use App\Models\AiSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiSetting>
 */
class AiSettingFactory extends Factory
{
    protected $model = AiSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'search_mode' => AISearchMode::External,
            'parameters' => [],
            'max_tokens' => 1024,
            'timeout' => 30,
            'fallback_provider' => null,
            'fallback_model' => null,
            'is_active' => true,
        ];
    }
}
