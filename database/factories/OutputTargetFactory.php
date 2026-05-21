<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Channel;
use App\Models\OutputTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

class OutputTargetFactory extends Factory
{
    protected $model = OutputTarget::class;

    public function definition(): array
    {
        return [
            'channel_id' => Channel::factory(),
            'name' => $this->faker->words(2, true),
            'output_url' => 'rtmp://localhost/live/' . $this->faker->word,
            'output_protocol' => 'rtmp',
            'trigger' => OutputTarget::TRIGGER_ALWAYS,
            'video_codec' => 'copy',
            'audio_codec' => 'copy',
            'video_bitrate_kbps' => null,
            'audio_bitrate_kbps' => null,
            'resolution' => null,
            'framerate' => null,
            'srt_passphrase' => null,
            'srt_latency_ms' => 120,
            'is_enabled' => true,
            'status' => OutputTarget::STATUS_IDLE,
            'pid' => null,
            'reconnect_attempts' => 0,
            'connected_at' => null,
            'last_error_at' => null,
            'last_error' => null,
            'bytes_sent' => 0,
            'metadata' => null,
        ];
    }

    public function rtmp(): static
    {
        return $this->state(fn(array $attributes) => [
            'output_protocol' => 'rtmp',
            'output_url' => 'rtmp://example.com/live/stream',
        ]);
    }

    public function srt(): static
    {
        return $this->state(fn(array $attributes) => [
            'output_protocol' => 'srt',
            'output_url' => 'srt://example.com:9000',
        ]);
    }

    public function icecast(): static
    {
        return $this->state(fn(array $attributes) => [
            'output_protocol' => 'icecast',
            'output_url' => 'icecast://source:password@example.com:8000/mount',
            'audio_codec' => 'libmp3lame',
            'video_codec' => null,
        ]);
    }

    public function enabled(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_enabled' => true,
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_enabled' => false,
        ]);
    }
}
