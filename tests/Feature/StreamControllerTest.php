<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Stream;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StreamControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_probe_url(): void
    {
        $response = $this->withApiToken()->postJson('/api/streams/probe', [
            'url' => 'rtmp://localhost/live/test',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.protocol', 'RTMP');
    }

    public function test_probe_rejects_invalid_url(): void
    {
        $response = $this->withApiToken()->postJson('/api/streams/probe', [
            'url' => 'ftp://invalid-protocol.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    public function test_can_get_stream_status(): void
    {
        $channel = Channel::factory()->create();
        Stream::factory()->for($channel)->create();

        $response = $this->withApiToken()->getJson("/api/streams/{$channel->id}/status");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'channel_id',
                    'channel_name',
                    'status',
                    'ingest_running',
                    'hls_available',
                ],
            ]);
    }

    public function test_can_get_stream_statistics(): void
    {
        $channel = Channel::factory()->create();
        Stream::factory()->for($channel)->create();

        $response = $this->withApiToken()->getJson("/api/streams/{$channel->id}/statistics");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'stream_id',
                    'duration_seconds',
                    'uptime_percentage',
                    'statistics',
                ],
            ]);
    }

    public function test_statistics_returns_404_when_no_active_stream(): void
    {
        $channel = Channel::factory()->create();

        $response = $this->withApiToken()->getJson("/api/streams/{$channel->id}/statistics");

        $response->assertStatus(404)
            ->assertJsonPath('error_code', 'NOT_FOUND');
    }

    public function test_can_get_recent_streams(): void
    {
        $channel = Channel::factory()->create();
        Stream::factory()->count(5)->for($channel)->create();

        $response = $this->withApiToken()->getJson("/api/streams/{$channel->id}/recent");

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }
}
