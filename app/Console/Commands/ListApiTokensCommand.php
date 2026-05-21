<?php

namespace App\Console\Commands;

use App\Models\ApiToken;
use Illuminate\Console\Command;

class ListApiTokensCommand extends Command
{
    protected $signature = 'api:token:list';
    protected $description = 'List all API tokens';

    public function handle(): int
    {
        $tokens = ApiToken::orderBy('created_at', 'desc')->get();

        if ($tokens->isEmpty()) {
            $this->info('No API tokens found. Generate one with: php artisan api:token:generate "Name"');
            return 0;
        }

        $this->table(
            ['ID', 'Name', 'Abilities', 'Last Used', 'Expires', 'Active'],
            $tokens->map(fn($t) => [
                $t->id,
                $t->name,
                implode(', ', $t->abilities ?? ['*']),
                $t->last_used_at?->diffForHumans() ?? 'Never',
                $t->expires_at?->toDateString() ?? 'Never',
                $t->is_active ? '<fg=green>Yes</>' : '<fg=red>No</>',
            ])
        );

        return 0;
    }
}
