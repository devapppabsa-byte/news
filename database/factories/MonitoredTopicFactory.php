<?php

namespace Database\Factories;

use App\Enums\SearchFrequency;
use App\Enums\TopicStatus;
use App\Models\MonitoredTopic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MonitoredTopic>
 */
class MonitoredTopicFactory extends Factory
{
    protected $model = MonitoredTopic::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'user_id' => User::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'description' => fake()->sentence(),
            'language' => 'es',
            'country' => null,
            'frequency' => SearchFrequency::Daily,
            'custom_interval_minutes' => null,
            'status' => TopicStatus::Active,
            'last_run_at' => null,
            'next_run_at' => now()->addDay(),
            'notify_email' => true,
            'notify_push' => false,
            'relevance_preferences' => null,
        ];
    }

    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TopicStatus::Paused,
            'next_run_at' => null,
        ]);
    }

    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => SearchFrequency::Manual,
            'next_run_at' => null,
        ]);
    }

    public function custom(int $minutes = 30): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => SearchFrequency::Custom,
            'custom_interval_minutes' => $minutes,
            'next_run_at' => now()->addMinutes($minutes),
        ]);
    }

    public function due(): static
    {
        return $this->state(fn (array $attributes) => [
            'next_run_at' => now()->subMinute(),
        ]);
    }
}
