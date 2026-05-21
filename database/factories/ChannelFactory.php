<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Channel;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChannelFactory extends Factory
{
    protected $model = Channel::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);
        $slug = \Illuminate\Support\Str::slug($name);

        return [
            'name' => $name,
            'slug' => $slug,
            'description' => $this->faker->sentence(),
            'vod_playlist_url' => 'https://example.com/vod/' . $slug . '.m3u8',
            'push_url' => null,
            'rtmp_push_url' => null,
            'is_active' => true,
            'is_live' => false,
            'is_icecast_enabled' => false,
            'is_relay_enabled' => false,
            'bitrate_kbps' => $this->faker->randomElement([128, 256, 512, 1024, 2048, 4000]),
            'resolution' => $this->faker->randomElement(['1920x1080', '1280x720', '640x360']),
            'metadata' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function live(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_live' => true,
        ]);
    }
}
