<?php

namespace Database\Factories;

use App\Enums\RunStatus;
use App\Enums\RunTrigger;
use App\Models\MonitoredTopic;
use App\Models\TopicRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TopicRun>
 */
class TopicRunFactory extends Factory
{
    protected $model = TopicRun::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = now()->subSeconds(30);

        return [
            'monitored_topic_id' => MonitoredTopic::factory(),
            'trigger' => RunTrigger::Scheduled,
            'status' => RunStatus::Success,
            'results_count' => fake()->numberBetween(0, 20),
            'error_message' => null,
            'started_at' => $startedAt,
            'finished_at' => now(),
        ];
    }

    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'trigger' => RunTrigger::Manual,
        ]);
    }

    public function failed(string $message = 'Search provider unavailable'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RunStatus::Failed,
            'results_count' => 0,
            'error_message' => $message,
        ]);
    }
}
