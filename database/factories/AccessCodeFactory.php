<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AccessCode;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccessCodeFactory extends Factory
{
    protected $model = AccessCode::class;

    public function definition(): array
    {
        return [
            'code' => AccessCode::generateRandomCode(12),
            'type' => $this->faker->randomElement([
                AccessCode::TYPE_LIBRARY_ONLY,
                AccessCode::TYPE_FULL_ACCESS,
                AccessCode::TYPE_PREMIUM,
            ]),
            'duration_days' => $this->faker->randomElement([30, 90, 365]),
            'expires_at' => $this->faker->optional(0.3)->dateTimeBetween('+1 month', '+1 year'),
            'max_uses' => $this->faker->randomElement([1, 5, 10, 100]),
            'uses_count' => 0,
            'is_active' => true,
            'metadata' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn(array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function fullyRedeemed(): static
    {
        return $this->state(fn(array $attributes) => [
            'max_uses' => 1,
            'uses_count' => 1,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }
}
