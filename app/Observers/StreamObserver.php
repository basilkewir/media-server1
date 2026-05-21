<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Stream;
use Illuminate\Support\Facades\Log;

class StreamObserver
{
    public function created(Stream $stream): void
    {
        Log::info('Stream record created', [
            'stream_id' => $stream->id,
            'channel_id' => $stream->channel_id,
            'type' => $stream->stream_type,
        ]);
    }

    public function updated(Stream $stream): void
    {
        if ($stream->isDirty('status') && in_array($stream->status, ['completed', 'error'])) {
            Log::info('Stream ended', [
                'stream_id' => $stream->id,
                'channel_id' => $stream->channel_id,
                'status' => $stream->status,
                'duration' => $stream->getDuration(),
            ]);
        }
    }

    public function deleted(Stream $stream): void
    {
        Log::info('Stream record deleted', [
            'stream_id' => $stream->id,
            'channel_id' => $stream->channel_id,
        ]);
    }
}
