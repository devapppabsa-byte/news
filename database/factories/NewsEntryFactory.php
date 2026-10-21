<?php

namespace Database\Factories;

use App\Enums\NewsItemStatus;
use App\Models\NewsEntry;
use App\Support\ContentHasher;
use App\Support\UrlNormalizer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsEntry>
 */
class NewsEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(4), '.');
        $url = fake()->unique()->url();
        $snippet = fake()->paragraph();
        $normalizer = new UrlNormalizer;
        $hasher = new ContentHasher;

        return [
            'title' => $title,
            'url' => $url,
            'normalized_url' => $normalizer->normalize($url),
            'url_hash' => $normalizer->hash($url),
            'content_hash' => $hasher->hash($title, $snippet),
            'source_name' => fake()->domainName(),
            'source_domain' => fake()->domainName(),
            'published_at' => fake()->optional()->dateTimeBetween('-1 month'),
            'discovered_at' => now(),
            'summary' => fake()->optional()->sentence(),
            'relevance_score' => fake()->numberBetween(0, 100),
            'source_available' => true,
            'source_checked_at' => now(),
            'status' => NewsItemStatus::New,
        ];
    }

    public function irrelevant(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NewsItemStatus::Irrelevant,
            'relevance_score' => 0,
        ]);
    }
}
