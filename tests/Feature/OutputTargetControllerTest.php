<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\OutputTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutputTargetControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_output_targets(): void
    {
        $channel = Channel::factory()->create();
        OutputTarget::factory()->count(3)->for($channel)->create();

        $response = $this->withApiToken()->getJson("/api/channels/{$channel->id}/outputs");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_output_target(): void
    {
        $channel = Channel::factory()->create();

        $response = $this->withApiToken()->postJson("/api/channels/{$channel->id}/outputs", [
            'name' => 'Test Output',
            'output_url' => 'rtmp://example.com/live/stream',
            'output_protocol' => 'rtmp',
            'trigger' => 'always',
            'video_codec' => 'copy',
            'audio_codec' => 'copy',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Test Output')
            ->assertJsonPath('data.output_protocol', 'rtmp');

        $this->assertDatabaseHas('output_targets', ['name' => 'Test Output']);
    }

    public function test_cannot_create_output_target_with_invalid_resolution(): void
    {
        $channel = Channel::factory()->create();

        $response = $this->withApiToken()->postJson("/api/channels/{$channel->id}/outputs", [
            'name' => 'Test Output',
            'output_url' => 'rtmp://example.com/live/stream',
            'output_protocol' => 'rtmp',
            'resolution' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['resolution']);
    }

    public function test_can_delete_output_target(): void
    {
        $channel = Channel::factory()->create();
        $target = OutputTarget::factory()->for($channel)->create();

        $response = $this->withApiToken()->deleteJson("/api/channels/{$channel->id}/outputs/{$target->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('output_targets', ['id' => $target->id]);
    }

    public function test_can_get_global_status(): void
    {
        OutputTarget::factory()->count(5)->create();

        $response = $this->withApiToken()->getJson('/api/outputs/status');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'summary',
                    'targets',
                ],
            ]);
    }

    public function test_unauthorized_access_to_wrong_channel_target_returns_404(): void
    {
        $channel1 = Channel::factory()->create();
        $channel2 = Channel::factory()->create();
        $target = OutputTarget::factory()->for($channel2)->create();

        $response = $this->withApiToken()->getJson("/api/channels/{$channel1->id}/outputs/{$target->id}");

        $response->assertStatus(404);
    }
}
