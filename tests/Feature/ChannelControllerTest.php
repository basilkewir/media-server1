<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\StreamEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChannelControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_channels(): void
    {
        Channel::factory()->count(3)->create();

        $response = $this->withApiToken()->getJson('/api/channels');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'slug', 'is_active', 'is_live'],
                ],
            ]);
    }

    public function test_can_create_channel(): void
    {
        $data = [
            'name' => 'Test Channel',
            'slug' => 'test-channel',
            'description' => 'A test channel',
            'bitrate_kbps' => 2000,
        ];

        $response = $this->withApiToken()->postJson('/api/channels', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Test Channel')
            ->assertJsonPath('data.slug', 'test-channel');

        $this->assertDatabaseHas('channels', ['slug' => 'test-channel']);
    }

    public function test_cannot_create_channel_with_invalid_slug(): void
    {
        $data = [
            'name' => 'Test Channel',
            'slug' => 'Invalid Slug!',
        ];

        $response = $this->withApiToken()->postJson('/api/channels', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_can_show_channel(): void
    {
        $channel = Channel::factory()->create();

        $response = $this->withApiToken()->getJson("/api/channels/{$channel->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $channel->id);
    }

    public function test_can_update_channel(): void
    {
        $channel = Channel::factory()->create(['name' => 'Old Name']);

        $response = $this->withApiToken()->putJson("/api/channels/{$channel->id}", [
            'name' => 'New Name',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'New Name');
    }

    public function test_can_delete_channel(): void
    {
        $channel = Channel::factory()->create();

        $response = $this->withApiToken()->deleteJson("/api/channels/{$channel->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('channels', ['id' => $channel->id]);
    }

    public function test_can_get_channel_events(): void
    {
        $channel = Channel::factory()->create();
        StreamEvent::factory()->count(5)->for($channel)->create();

        $response = $this->withApiToken()->getJson("/api/channels/{$channel->id}/events");

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $response = $this->getJson('/api/channels');

        $response->assertStatus(401)
            ->assertJsonPath('error_code', 'UNAUTHORIZED');
    }
}
