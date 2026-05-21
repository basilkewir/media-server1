<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AccessCode;
use App\Models\CodeRedemption;
use Illuminate\Database\Eloquent\Factories\Factory;

class CodeRedemptionFactory extends Factory
{
    protected $model = CodeRedemption::class;

    public function definition(): array
    {
        return [
            'access_code_id' => AccessCode::factory(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'redeemed_at' => now(),
            'expires_at' => now()->addDays(30),
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

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }
}
