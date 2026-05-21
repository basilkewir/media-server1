<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Channel;
use App\Models\Stream;
use Illuminate\Database\Eloquent\Factories\Factory;

class StreamFactory extends Factory
{
    protected $model = Stream::class;

    public function definition(): array
    {
        return [
            'channel_id' => Channel::factory(),
            'status' => 'active',
            'stream_type' => 'live',
            'source_url' => 'rtmp://localhost/live/test',
            'input_protocol' => 'RTMP',
            'started_at' => now(),
            'ended_at' => null,
            'bitrate_kbps' => 2000,
            'resolution' => '1920x1080',
            'viewers' => $this->faker->numberBetween(0, 10000),
            'metadata' => null,
        ];
    }

    public function fallback(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'fallback',
            'stream_type' => 'vod',
            'source_url' => 'https://example.com/vod/playlist.m3u8',
            'input_protocol' => 'HLS',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'completed',
            'ended_at' => now(),
        ]);
    }
}
