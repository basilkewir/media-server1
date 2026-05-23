<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }

    protected function schedule(Schedule $schedule): void
    {
        // Process VOD schedules every minute for active channels
        $schedule->command('vod:process-schedules')
            ->everyMinute()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/vod-scheduler.log'));

        // Monitor audio relay processes every minute
        $schedule->command('audio-relay:monitor')
            ->everyMinute()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/audio-relay.log'));
    }
}
