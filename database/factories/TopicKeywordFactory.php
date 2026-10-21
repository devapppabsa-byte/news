<?php

namespace Database\Factories;

use App\Enums\KeywordType;
use App\Models\MonitoredTopic;
use App\Models\TopicKeyword;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TopicKeyword>
 */
class TopicKeywordFactory extends Factory
{
    protected $model = TopicKeyword::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'monitored_topic_id' => MonitoredTopic::factory(),
            'type' => KeywordType::Include,
            'value' => fake()->unique()->word(),
        ];
    }

    public function excluded(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => KeywordType::Exclude,
        ]);
    }
}
