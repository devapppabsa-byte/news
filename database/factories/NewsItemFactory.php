<?php

namespace Database\Factories;

use App\Models\MonitoredTopic;
use App\Models\NewsItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<NewsItem>
 */
class NewsItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'user_id' => User::factory(),
            'topic_id' => null,
            'title' => rtrim($title, '.'),
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 99999),
            'body' => fake()->paragraphs(3, true),
            'url' => fake()->url(),
            'source_domain' => fake()->domainName(),
            'relevance' => fake()->numberBetween(0, 100),
            'status' => 'draft',
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function forTopic(MonitoredTopic $topic): static
    {
        return $this->state(fn (array $attributes) => [
            'topic_id' => $topic->id,
            'user_id' => $topic->user_id,
        ]);
    }
}
