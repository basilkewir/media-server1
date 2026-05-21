<?php

namespace App\Console\Commands;

use App\Models\ApiToken;
use Illuminate\Console\Command;

class RevokeApiTokenCommand extends Command
{
    protected $signature = 'api:token:revoke {id : Token ID to revoke}';
    protected $description = 'Revoke an API token by ID';

    public function handle(): int
    {
        $token = ApiToken::find($this->argument('id'));

        if (!$token) {
            $this->error("Token ID {$this->argument('id')} not found.");
            return 1;
        }

        $token->update(['is_active' => false]);
        $this->info("Token '{$token->name}' (ID: {$token->id}) revoked.");

        return 0;
    }
}
