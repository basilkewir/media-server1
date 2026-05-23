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
        $schedule->command('vod:process-schedules')
            ->everyMinute()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/vod-scheduler.log'));

        $schedule->command('audio-relay:monitor')
            ->everyMinute()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/audio-relay.log'));

        $schedule->command('health:check-all')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/health-check.log'));

        $schedule->call(function () {
            $retentionDays = config('streaming.retention_days', 30);

            \App\Models\StreamStatistic::where('created_at', '<', now()->subDays($retentionDays))->delete();
            \App\Models\OutputTargetLog::where('created_at', '<', now()->subDays($retentionDays))->delete();
            \App\Models\RelayBroadcastLog::where('created_at', '<', now()->subDays($retentionDays))->delete();
            \App\Models\StreamEvent::where('created_at', '<', now()->subDays($retentionDays))->delete();
        })
            ->daily()
            ->appendOutputTo(storage_path('logs/cleanup.log'))
            ->description('Prune old stream statistics, logs, and events');
    }
}
