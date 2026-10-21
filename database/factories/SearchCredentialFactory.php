<?php

namespace Database\Factories;

use App\Models\SearchCredential;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SearchCredential>
 */
class SearchCredentialFactory extends Factory
{
    protected $model = SearchCredential::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider' => 'brave',
            'name' => 'key',
            'value' => 'test-secret-value',
            'is_active' => true,
        ];
    }
}
