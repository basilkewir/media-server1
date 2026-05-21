<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StreamHealthMonitor;
use App\Services\OutputManager;

class StreamMonitorCommand extends Command
{
    protected $signature = 'stream:monitor {--interval=5 : Health check interval in seconds}';
    protected $description = 'Monitor stream health, trigger VOD fallback, and maintain output targets';

    public function __construct(
        protected StreamHealthMonitor $monitor,
        protected OutputManager       $outputManager,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $interval = max(1, (int) $this->option('interval'));
        $this->info("Stream + output monitor started (interval: {$interval}s)");

        while (true) {
            try {
                // 1. Check ingest stream health / VOD fallback
                $this->monitor->checkAllChannels();

                // 2. Check all output target processes, reconnect dead ones
                $this->outputManager->checkAllTargets();
            } catch (\Exception $e) {
                $this->error('Monitor error: ' . $e->getMessage());
            }

            sleep($interval);
        }

        return 0;
    }
}
