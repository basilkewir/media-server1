<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\VodSchedulerService;
use Illuminate\Console\Command;

class ProcessVodSchedules extends Command
{
    protected $signature = 'vod:process-schedules';
    protected $description = 'Process VOD schedules for all active channels';

    public function handle(VodSchedulerService $scheduler): int
    {
        $this->info('Processing VOD schedules...');
        $scheduler->processSchedules();
        $this->info('Done.');
        return 0;
    }
}
