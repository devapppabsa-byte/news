<?php

namespace Database\Factories;

use App\Enums\DeliveryStatus;
use App\Enums\NotificationMode;
use App\Models\MonitoredTopic;
use App\Models\NewsEntry;
use App\Models\NotificationDelivery;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<NotificationDelivery>
 */
class NotificationDeliveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'monitored_topic_id' => MonitoredTopic::factory(),
            'news_entry_id' => NewsEntry::factory(),
            'channel' => 'mail',
            'mode' => NotificationMode::Individual,
            'dedupe_key' => Str::random(40),
            'status' => DeliveryStatus::Queued,
            'attempts' => 0,
            'items_count' => 1,
            'queued_at' => now(),
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeliveryStatus::Sent,
            'sent_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeliveryStatus::Failed,
            'failed_at' => now(),
            'error_message' => 'SMTP timeout',
        ]);
    }

    public function digest(): static
    {
        return $this->state(fn (array $attributes) => [
            'mode' => NotificationMode::Digest,
            'news_entry_id' => null,
            'items_count' => 3,
        ]);
    }
}
