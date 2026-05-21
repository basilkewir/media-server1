<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Channel;
use App\Models\Stream;
use App\Models\StreamEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class StreamingService
{
    public function __construct(
        protected ProtocolDetector $protocol,
        protected OutputManager    $outputManager,
    ) {}

    public function startStream(Channel $channel, string $sourceUrl): Stream
    {
        $sourceUrl = $this->sanitizeUrl($sourceUrl);

        return DB::transaction(function () use ($channel, $sourceUrl) {
            $this->stopIngest($channel);

            $stream = Stream::create([
                'channel_id'     => $channel->id,
                'status'         => 'active',
                'stream_type'    => 'live',
                'source_url'     => $sourceUrl,
                'input_protocol' => $this->protocol->label($sourceUrl),
                'started_at'     => now(),
            ]);

            $this->startIngest($channel, $sourceUrl, loop: false);
            $channel->update(['is_live' => true, 'push_url' => $sourceUrl]);
            $this->outputManager->startChannelOutputs($channel, 'live');

            StreamEvent::create([
                'channel_id' => $channel->id,
                'event_type' => StreamEvent::EVENT_STREAM_STARTED,
                'message'    => "Live [{$this->protocol->label($sourceUrl)}]: {$channel->name}",
                'severity'   => StreamEvent::SEVERITY_INFO,
            ]);

            return $stream;
        });
    }

    public function stopStream(Channel $channel): bool
    {
        return DB::transaction(function () use ($channel) {
            $this->outputManager->stopChannelOutputs($channel);
            $this->stopIngest($channel);

            $channel->streams()
                ->whereIn('status', ['active', 'fallback'])
                ->update(['status' => 'completed', 'ended_at' => now()]);

            $channel->update(['is_live' => false]);

            StreamEvent::create([
                'channel_id' => $channel->id,
                'event_type' => StreamEvent::EVENT_STREAM_STOPPED,
                'message'    => "Stream stopped: {$channel->name}",
                'severity'   => StreamEvent::SEVERITY_INFO,
            ]);

            return true;
        });
    }

    public function switchToVODFallback(Channel $channel): ?Stream
    {
        if (!$channel->vod_playlist_url) {
            return null;
        }

        $vodUrl = $this->sanitizeUrl($channel->vod_playlist_url);

        return DB::transaction(function () use ($channel, $vodUrl) {
            $this->outputManager->stopChannelOutputs($channel, 'live');
            $this->stopIngest($channel);

            $channel->streams()
                ->where('status', 'active')
                ->update(['status' => 'completed', 'ended_at' => now()]);

            $fallback = Stream::create([
                'channel_id'     => $channel->id,
                'status'         => 'fallback',
                'stream_type'    => 'vod',
                'source_url'     => $vodUrl,
                'input_protocol' => $this->protocol->label($vodUrl),
                'started_at'     => now(),
            ]);

            $this->startIngest($channel, $vodUrl, loop: true);
            $channel->update(['is_live' => true]);
            $this->outputManager->startChannelOutputs($channel, 'fallback');

            StreamEvent::create([
                'channel_id' => $channel->id,
                'event_type' => StreamEvent::EVENT_VOD_FALLBACK,
                'message'    => "VOD fallback [{$this->protocol->label($vodUrl)}]: {$channel->name}",
                'severity'   => StreamEvent::SEVERITY_WARNING,
            ]);

            return $fallback;
        });
    }

    public function recoverFromFallback(Channel $channel, string $liveUrl): Stream
    {
        $liveUrl = $this->sanitizeUrl($liveUrl);

        return DB::transaction(function () use ($channel, $liveUrl) {
            $this->outputManager->stopChannelOutputs($channel, 'fallback');
            $this->stopIngest($channel);

            $channel->streams()
                ->where('status', 'fallback')
                ->update(['status' => 'completed', 'ended_at' => now()]);

            StreamEvent::create([
                'channel_id' => $channel->id,
                'event_type' => StreamEvent::EVENT_FALLBACK_RECOVERED,
                'message'    => "Recovered to live [{$this->protocol->label($liveUrl)}]: {$channel->name}",
                'severity'   => StreamEvent::SEVERITY_INFO,
            ]);

            return $this->startStream($channel, $liveUrl);
        });
    }

    protected function startIngest(Channel $channel, string $sourceUrl, bool $loop): void
    {
        $ffmpeg    = config('services.ffmpeg.path', '/usr/bin/ffmpeg');
        $loglevel  = config('services.ffmpeg.log_level', 'warning');
        $outputDir = $this->outputDir($channel);

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $pipe = $this->pipePath($channel);
        if (!file_exists($pipe)) {
            posix_mkfifo($pipe, 0600);
        }

        $cmd = [$ffmpeg, '-y', '-loglevel', $loglevel];

        if ($loop) {
            array_push($cmd, '-stream_loop', '-1');
        }

        if ($loop || $this->protocol->detect($sourceUrl) === 'file') {
            $cmd[] = '-re';
        }

        foreach ($this->protocol->getInputArgs($sourceUrl) as $arg) {
            $cmd[] = $arg;
        }

        $cmd[] = '-i';
        $cmd[] = $sourceUrl;

        $hlsDir      = $outputDir;
        $hlsDuration = (string) config('services.stream.hls_segment_duration', 2);
        $hlsListSize = (string) config('services.stream.hls_segments_in_playlist', 10);

        $teeOutputs = implode('|', [
            "[f=mpegts:onfail=ignore]{$pipe}",
            "[f=hls:hls_time={$hlsDuration}:hls_list_size={$hlsListSize}"
                . ":hls_flags=delete_segments+append_list+independent_segments"
                . ":hls_segment_type=mpegts"
                . ":hls_segment_filename={$hlsDir}/seg%05d.ts]{$hlsDir}/playlist.m3u8",
        ]);

        array_push($cmd,
            '-c:v', 'copy',
            '-c:a', 'copy',
            '-f', 'tee',
            '-map', '0',
            $teeOutputs
        );

        $process = new Process($cmd);
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        $process->start();

        $pid = $process->getPid();
        if ($pid) {
            Cache::put("ingest_pid:{$channel->id}", $pid, now()->addHours(24));
        }

        Log::info('Ingest started', [
            'channel'  => $channel->slug,
            'pid'      => $pid,
            'protocol' => $this->protocol->label($sourceUrl),
            'loop'     => $loop,
            'pipe'     => $pipe,
        ]);
    }

    protected function stopIngest(Channel $channel): void
    {
        $pid = Cache::get("ingest_pid:{$channel->id}");

        if ($pid) {
            $this->kill((int) $pid);
            Cache::forget("ingest_pid:{$channel->id}");
            Log::info('Ingest stopped', ['channel' => $channel->slug, 'pid' => $pid]);
        }

        $pipe = $this->pipePath($channel);
        if (file_exists($pipe)) {
            @unlink($pipe);
        }
    }

    public function outputDir(Channel $channel): string
    {
        return storage_path("streams/{$channel->slug}");
    }

    public function pipePath(Channel $channel): string
    {
        return $this->outputDir($channel) . '/live.ts';
    }

    public function hlsPlaylistPath(Channel $channel): string
    {
        return $this->outputDir($channel) . '/playlist.m3u8';
    }

    public function isIngestRunning(Channel $channel): bool
    {
        $pid = Cache::get("ingest_pid:{$channel->id}");
        return $pid && is_int($pid) && file_exists("/proc/{$pid}");
    }

    public function isFFmpegRunning(Channel $channel): bool
    {
        return $this->isIngestRunning($channel);
    }

    protected function kill(int $pid): void
    {
        if ($pid <= 0) return;

        if (function_exists('posix_kill')) {
            @posix_kill($pid, 15);
            usleep(500000);
            if (file_exists("/proc/{$pid}")) {
                @posix_kill($pid, 9);
            }
        } else {
            $safePid = escapeshellarg((string) $pid);
            @exec("kill -15 {$safePid} 2>/dev/null");
            usleep(500000);
            if (file_exists("/proc/{$pid}")) {
                @exec("kill -9 {$safePid} 2>/dev/null");
            }
        }
    }

    public function getStreamStatus(Channel $channel): array
    {
        $stream  = $channel->activeStream();
        $outputs = $channel->outputTargets()
            ->where('is_enabled', true)
            ->get()
            ->map->toStatusArray();

        return [
            'channel_id'             => $channel->id,
            'channel_name'           => $channel->name,
            'is_live'                => $channel->is_live,
            'stream_type'            => $stream?->stream_type,
            'status'                 => $stream?->status ?? 'offline',
            'input_protocol'         => $stream?->input_protocol,
            'source_url'             => $stream?->source_url,
            'duration'               => $stream?->getDuration(),
            'viewers'                => $stream?->viewers ?? 0,
            'ingest_running'         => $this->isIngestRunning($channel),
            'pipe_exists'            => file_exists($this->pipePath($channel)),
            'hls_available'          => file_exists($this->hlsPlaylistPath($channel)),
            'vod_fallback_available' => !is_null($channel->vod_playlist_url),
            'hls_url'                => url("/streams/{$channel->slug}/playlist.m3u8"),
            'output_targets'         => $outputs,
            'output_count'           => $outputs->count(),
            'active_outputs'         => $outputs->whereIn('status', ['connecting', 'connected', 'reconnecting'])->count(),
        ];
    }

    /**
     * Sanitize a stream URL to prevent command injection.
     * While Symfony Process array args prevent shell injection,
     * we still validate the URL format.
     */
    protected function sanitizeUrl(string $url): string
    {
        $url = trim($url);
        $allowedPrefixes = ['rtmp://', 'rtmps://', 'rtsp://', 'srt://', 'udp://', 'rtp://', 'tcp://', 'http://', 'https://', 'file://', '/'];
        $hasAllowedPrefix = false;
        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with(strtolower($url), $prefix)) {
                $hasAllowedPrefix = true;
                break;
            }
        }
        if (!$hasAllowedPrefix) {
            throw new \InvalidArgumentException('Invalid stream URL format.');
        }
        return $url;
    }
}
