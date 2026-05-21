<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ApiToken;
use App\Models\Channel;
use App\Models\OutputTarget;
use App\Models\RelayServer;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create a default API token for immediate use
        $token = ApiToken::generate('Default Admin Token');
        $this->command->info('Default API Token: ' . $token->plain_token);

        // Create sample channels
        $channels = Channel::factory()->count(3)->sequence(
            ['name' => 'Main Channel', 'slug' => 'main'],
            ['name' => 'Sports Channel', 'slug' => 'sports'],
            ['name' => 'News Channel', 'slug' => 'news'],
        )->create();

        // Create output targets for each channel
        foreach ($channels as $channel) {
            OutputTarget::factory()->count(2)->for($channel)->create();
        }

        // Create relay servers
        RelayServer::factory()->count(2)->sequence(
            ['name' => 'Primary Icecast', 'server_type' => 'icecast', 'port' => 8000],
            ['name' => 'Backup RTMP', 'server_type' => 'rtmp', 'port' => 1935],
        )->create();

        $this->command->info('Demo data seeded successfully.');
    }
}
