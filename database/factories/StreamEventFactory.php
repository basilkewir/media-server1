<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Channel;
use App\Models\StreamEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

class StreamEventFactory extends Factory
{
    protected $model = StreamEvent::class;

    public function definition(): array
    {
        return [
            'channel_id' => Channel::factory(),
            'event_type' => $this->faker->randomElement([
                StreamEvent::EVENT_STREAM_STARTED,
                StreamEvent::EVENT_STREAM_STOPPED,
                StreamEvent::EVENT_VOD_FALLBACK,
                StreamEvent::EVENT_FALLBACK_RECOVERED,
            ]),
            'message' => $this->faker->sentence(),
            'severity' => $this->faker->randomElement([
                StreamEvent::SEVERITY_INFO,
                StreamEvent::SEVERITY_WARNING,
                StreamEvent::SEVERITY_ERROR,
            ]),
            'metadata' => null,
        ];
    }
}
