<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\StreamHealthMonitor;
use App\Services\OutputManager;

class StreamMonitorCommand extends Command
{
    protected $signature = 'stream:monitor {--interval=5 : Health check interval in seconds}';
    protected $description = 'Monitor stream health, trigger VOD fallback, and maintain output targets';

    public function __construct() {
        parent::__construct();
    }

    public function handle(): int
    {
        $interval = max(1, (int) $this->option('interval'));
        $this->info("Stream + output monitor started (interval: {$interval}s)");

        // Wait for DB and required tables before starting
        $attempts = 0;
        while ($attempts < 30) {
            try {
                DB::connection()->getPdo();
                if (Schema::hasTable('channels') && Schema::hasTable('streams')) {
                    break;
                }
                $this->warn('Waiting for migrations...');
            } catch (\Exception $e) {
                $this->warn("DB not ready: {$e->getMessage()}");
            }
            sleep(5);
            $attempts++;
        }

        while (true) {
            try {
                app(StreamHealthMonitor::class)->checkAllChannels();
                app(OutputManager::class)->checkAllTargets();
            } catch (\Exception $e) {
                $this->error('Monitor error: ' . $e->getMessage());
            }
            sleep($interval);
        }

        return 0;
    }
}
