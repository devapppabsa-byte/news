<?php

namespace Database\Factories;

use App\Models\Preference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Preference>
 */
class PreferenceFactory extends Factory
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
            'locale' => 'es',
            'timezone' => 'UTC',
            'email_notifications' => true,
            'notification_mode' => 'digest',
            'push_notifications' => true,
            'push_private' => true,
            'digest_frequency' => 'daily',
            'theme' => 'system',
        ];
    }
}
