<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Stream;
use App\Models\StreamStatistic;
use Illuminate\Database\Eloquent\Factories\Factory;

class StreamStatisticFactory extends Factory
{
    protected $model = StreamStatistic::class;

    public function definition(): array
    {
        return [
            'stream_id' => Stream::factory(),
            'viewers' => $this->faker->numberBetween(0, 50000),
            'bitrate_kbps' => $this->faker->randomElement([500, 1000, 2000, 4000, 8000]),
            'framerate' => $this->faker->randomElement([24, 25, 30, 60]),
            'is_healthy' => $this->faker->boolean(90),
            'metadata' => null,
        ];
    }
}
