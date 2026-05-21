<?php

namespace App\Console\Commands;

use App\Models\RelayBroadcast;
use App\Services\RelayBroadcastService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RelayHealthCheckCommand extends Command
{
    protected $signature = 'relay:health-check {--interval=30 : Check interval in seconds}';
    protected $description = 'Monitor relay broadcast health and auto-restart failed relays';

    public function __construct(protected RelayBroadcastService $relayService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $interval = max(5, (int) $this->option('interval'));
        $this->info("Relay health monitor started (interval: {$interval}s)");

        while (true) {
            try {
                $this->runChecks();
            } catch (\Exception $e) {
                Log::error('Relay health check error: ' . $e->getMessage());
                $this->error($e->getMessage());
            }
            sleep($interval);
        }

        return 0;
    }

    protected function runChecks(): void
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, RelayBroadcast> $relays */
        $relays = RelayBroadcast::where('is_active', true)
            ->with(['channel', 'relayServer'])
            ->get();

        if ($relays->isEmpty()) {
            return;
        }

        $this->line('[' . now()->format('H:i:s') . "] Checking {$relays->count()} relay(s)...");

        foreach ($relays as $relay) {
            /** @var RelayBroadcast $relay */
            $healthy = $this->relayService->checkRelayHealth($relay);

            if (!$healthy) {
                $this->warn("Relay {$relay->id} unhealthy ({$relay->status}), attempting restart...");
                $this->attemptRestart($relay);
            } else {
                $this->line("✓ Relay {$relay->id}: {$relay->channel->name} → {$relay->relayServer->name}");
            }
        }
    }

    protected function attemptRestart(RelayBroadcast $relay): void
    {
        try {
            /** @var RelayBroadcast $relay */
            $relay->refresh();

            if (!$relay->channel->is_relay_enabled) {
                return;
            }

            $this->relayService->startRelay($relay->channel, $relay->relayServer);
            $this->info("✓ Relay {$relay->id} restarted");

            $relay->logs()->create([
                'event_type' => 'relay_restarted',
                'message'    => 'Auto-restarted after health check failure',
                'status'     => 'success',
            ]);
        } catch (\Exception $e) {
            $this->error("✗ Relay {$relay->id} restart failed: " . $e->getMessage());

            $relay->logs()->create([
                'event_type' => 'relay_restart_failed',
                'message'    => 'Auto-restart failed: ' . $e->getMessage(),
                'status'     => 'error',
            ]);
        }
    }
}
