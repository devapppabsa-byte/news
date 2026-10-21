<?php

namespace Database\Factories;

use App\Enums\SourceType;
use App\Models\MonitoredTopic;
use App\Models\TopicSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TopicSource>
 */
class TopicSourceFactory extends Factory
{
    protected $model = TopicSource::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'monitored_topic_id' => MonitoredTopic::factory(),
            'type' => SourceType::Preferred,
            'domain' => fake()->unique()->domainName(),
        ];
    }

    public function excluded(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => SourceType::Excluded,
        ]);
    }
}
