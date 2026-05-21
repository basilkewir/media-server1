<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Channel;
use App\Services\StreamHealthMonitor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckStreamHealthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;

    public function __construct(public Channel $channel) {}

    public function handle(StreamHealthMonitor $monitor): void
    {
        $result = $monitor->checkStreamHealth($this->channel);

        Log::debug('Stream health check completed', [
            'channel_id' => $this->channel->id,
            'healthy' => $result,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CheckStreamHealthJob failed', [
            'channel_id' => $this->channel->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
