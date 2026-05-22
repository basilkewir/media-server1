<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ApiToken;
use App\Models\Channel;
use App\Models\OutputTarget;
use App\Models\RelayServer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create default admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@mediaserver.local'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );
        $this->command->info('Default Admin Login:');
        $this->command->info('  Email:    admin@mediaserver.local');
        $this->command->info('  Password: admin123');
        $this->command->info('');

        // Create a default API token for immediate use
        $token = ApiToken::generate('Default Admin Token');
        $this->command->info('Default API Token: ' . $token->plain_token);

        // Create sample channels
        $channels = collect([
            ['name' => 'Main Channel', 'slug' => 'main'],
            ['name' => 'Sports Channel', 'slug' => 'sports'],
            ['name' => 'News Channel', 'slug' => 'news'],
        ])->map(fn($attrs) => Channel::firstOrCreate(['slug' => $attrs['slug']], $attrs));

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
