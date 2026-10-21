<?php

namespace Database\Factories;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PushSubscription>
 */
class PushSubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $endpoint = 'https://fcm.googleapis.com/fcm/send/'.fake()->uuid();

        return [
            'user_id' => User::factory(),
            'endpoint' => $endpoint,
            'endpoint_hash' => PushSubscription::hashEndpoint($endpoint),
            'public_key' => fake()->sha1(),
            'auth_token' => fake()->sha1(),
            'content_encoding' => 'aesgcm',
            'device_label' => fake()->randomElement(['Chrome', 'Firefox', 'Safari']),
            'user_agent' => fake()->userAgent(),
            'last_seen_at' => now(),
            'expired_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expired_at' => now(),
        ]);
    }
}
