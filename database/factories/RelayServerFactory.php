<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\RelayServer;
use Illuminate\Database\Eloquent\Factories\Factory;

class RelayServerFactory extends Factory
{
    protected $model = RelayServer::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company() . ' Relay',
            'hostname' => $this->faker->domainName(),
            'port' => $this->faker->randomElement([8000, 1935, 8080]),
            'username' => 'source',
            'password' => $this->faker->password(12),
            'server_type' => $this->faker->randomElement(['icecast', 'rtmp', 'shoutcast']),
            'is_active' => true,
            'max_listeners' => 1000,
            'location' => $this->faker->city(),
            'bandwidth_kbps' => 10000,
            'metadata' => null,
        ];
    }
}
