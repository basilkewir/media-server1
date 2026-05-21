<?php

namespace App\Console\Commands;

use App\Models\ApiToken;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateApiTokenCommand extends Command
{
    protected $signature = 'api:token:generate
                            {name : A descriptive name for this token}
                            {--abilities=* : Comma-separated abilities e.g. read,write,admin (default: all)}
                            {--expires= : Expiry in days (default: never)}';

    protected $description = 'Generate a new API token for MediaServer access';

    public function handle(): int
    {
        $name      = $this->argument('name');
        $abilities = $this->option('abilities');
        $expires   = $this->option('expires');

        // Generate a cryptographically secure plain token
        $plainToken = Str::random(40);

        $token = ApiToken::create([
            'name'       => $name,
            'token'      => hash('sha256', $plainToken),   // store hash only
            'abilities'  => empty($abilities) ? ['*'] : $abilities,
            'expires_at' => $expires ? now()->addDays((int) $expires) : null,
            'is_active'  => true,
        ]);

        $this->newLine();
        $this->line('  <fg=green>✓ API token generated successfully</>');
        $this->newLine();
        $this->line("  <fg=yellow>Name:</>      {$name}");
        $this->line("  <fg=yellow>Token ID:</>  {$token->id}");
        $this->line("  <fg=yellow>Abilities:</> " . implode(', ', $token->abilities));
        $this->line("  <fg=yellow>Expires:</>   " . ($token->expires_at ? $token->expires_at->toDateTimeString() : 'Never'));
        $this->newLine();
        $this->line('  <fg=cyan>Your API token (copy now — shown only once):</>');
        $this->newLine();
        $this->line("  <fg=white;bg=blue>  {$plainToken}  </>");
        $this->newLine();
        $this->line('  Usage:');
        $this->line("  <fg=gray>curl -H \"Authorization: Bearer {$plainToken}\" http://localhost/api/channels</>");
        $this->newLine();

        return 0;
    }
}
