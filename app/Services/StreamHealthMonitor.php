<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\StreamEvent;
use Illuminate\Support\Facades\Log;
use Exception;

class StreamHealthMonitor
{
    public function __construct(
        protected StreamingService $streamingService,
        protected ProtocolDetector $protocol
    ) {}

    public function checkStreamHealth(Channel $channel): bool
    {
        try {
            $stream = $channel->activeStream();

            if (!$stream) {
                if ($channel->vod_playlist_url && $channel->is_active) {
                    $this->triggerVODFallback($channel);
                }
                return false;
            }

            // On VOD fallback — just keep FFmpeg alive
            if ($stream->status === 'fallback') {
                if (!$this->streamingService->isFFmpegRunning($channel)) {
                    Log::warning('VOD fallback FFmpeg died, restarting', ['channel' => $channel->slug]);
                    $this->streamingService->switchToVODFallback($channel);
                }
                return true;
            }

            // Live stream — check source reachability using correct protocol method
            $timeout     = (int) config('services.stream.source_timeout', 5);
            $reachable   = $this->protocol->isReachable($stream->source_url, $timeout);

            if (!$reachable) {
                Log::warning('Live source unreachable, switching to VOD fallback', [
                    'channel'  => $channel->slug,
                    'protocol' => $this->protocol->label($stream->source_url),
                    'source'   => $stream->source_url,
                ]);

                if ($channel->vod_playlist_url) {
                    $this->triggerVODFallback($channel);
                } else {
                    $this->streamingService->stopStream($channel);
                }
                return false;
            }

            // Source reachable but FFmpeg died — restart
            if (!$this->streamingService->isFFmpegRunning($channel)) {
                Log::warning('FFmpeg died, restarting live stream', ['channel' => $channel->slug]);
                $this->streamingService->startStream($channel, $stream->source_url);
                return false;
            }

            return true;
        } catch (Exception $e) {
            Log::error('Health check error', ['channel' => $channel->slug, 'error' => $e->getMessage()]);
            return false;
        }
    }

    protected function triggerVODFallback(Channel $channel): void
    {
        $this->streamingService->switchToVODFallback($channel);

        StreamEvent::create([
            'channel_id' => $channel->id,
            'event_type' => StreamEvent::EVENT_VOD_FALLBACK,
            'message'    => 'Auto-switched to VOD fallback (live source unavailable)',
            'severity'   => StreamEvent::SEVERITY_WARNING,
        ]);
    }

    public function checkAllChannels(): void
    {
        Channel::where('is_active', true)->each(fn(Channel $ch) => $this->checkStreamHealth($ch));
    }

    public function getHealthStatistics(Channel $channel): array
    {
        $stream = $channel->activeStream();

        return [
            'channel_id'        => $channel->id,
            'status'            => $stream?->status ?? 'offline',
            'stream_type'       => $stream?->stream_type,
            'input_protocol'    => $stream?->input_protocol,
            'ffmpeg_running'    => $this->streamingService->isFFmpegRunning($channel),
            'uptime_percentage' => $stream?->getUptimePercentage() ?? 0,
            'duration'          => $stream?->getDuration() ?? 0,
        ];
    }
}
