<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ApiToken;
use Illuminate\Console\Command;

class GenerateApiToken extends Command
{
    protected $signature = 'api:token:generate
                            {name : Name of the token}
                            {--expires= : Expiration in days}
                            {--ips= : Comma-separated allowed IPs}';

    protected $description = 'Generate a new API token for authentication';

    public function handle(): int
    {
        $name = $this->argument('name');
        $expiresDays = $this->option('expires');
        $ips = $this->option('ips');

        $expiresAt = $expiresDays ? now()->addDays((int) $expiresDays) : null;
        $allowedIps = $ips ? array_map('trim', explode(',', $ips)) : [];

        $token = ApiToken::generate($name, $expiresAt, $allowedIps);

        $this->info('API token generated successfully!');
        $this->newLine();
        $this->line('Name: ' . $token->name);
        $this->line('Token: ' . $token->plain_token);
        $this->line('Expires: ' . ($token->expires_at?->toDateTimeString() ?? 'Never'));
        $this->newLine();
        $this->warn('Store this token safely — it will not be shown again.');

        return self::SUCCESS;
    }
}
