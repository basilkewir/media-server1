<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\OutputTarget;
use App\Services\OutputManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StartOutputTargetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(public OutputTarget $target) {}

    public function handle(OutputManager $outputManager): void
    {
        Log::info('Starting output target via queue job', [
            'target_id' => $this->target->id,
            'channel_id' => $this->target->channel_id,
        ]);

        $outputManager->startTarget($this->target);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('StartOutputTargetJob failed', [
            'target_id' => $this->target->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
