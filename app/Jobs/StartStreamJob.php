<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Channel;
use App\Services\StreamingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StartStreamJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public Channel $channel,
        public string $sourceUrl,
    ) {}

    public function handle(StreamingService $streamingService): void
    {
        Log::info('Starting stream via queue job', [
            'channel_id' => $this->channel->id,
            'source_url' => $this->sourceUrl,
        ]);

        $streamingService->startStream($this->channel, $this->sourceUrl);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('StartStreamJob failed', [
            'channel_id' => $this->channel->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
