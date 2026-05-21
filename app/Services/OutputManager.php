<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Channel;
use App\Models\OutputTarget;
use App\Models\OutputTargetLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Container\Container;
use Symfony\Component\Process\Process;
use Exception;

class OutputManager
{
    const MAX_RECONNECTS  = 10;
    const RECONNECT_DELAY = 3;

    public function __construct(
        protected ProtocolDetector $protocol,
    ) {}

    protected function streaming(): StreamingService
    {
        return Container::getInstance()->make(StreamingService::class);
    }

    public function startTarget(OutputTarget $target): void
    {
        if (!$target->is_enabled) {
            return;
        }

        $this->killTargetProcess($target);

        $pipe = $this->streaming()->pipePath($target->channel);
        $hls  = $this->streaming()->hlsPlaylistPath($target->channel);

        $needsTranscode = $this->targetNeedsTranscode($target);

        if (!$needsTranscode && file_exists($pipe)) {
            $source     = $pipe;
            $sourceType = 'pipe';
        } elseif (file_exists($hls)) {
            $source     = $hls;
            $sourceType = 'hls';
        } else {
            $this->logTarget($target, 'error', 'no_source',
                'Neither pipe nor HLS source available — start the channel stream first');
            $target->update([
                'status'       => OutputTarget::STATUS_ERROR,
                'last_error'   => 'No source available',
                'last_error_at'=> now(),
            ]);
            return;
        }

        $cmd = $this->buildCommand($target, $source, $sourceType);

        $process = new Process($cmd);
        $process->setTimeout(null);
        $process->setIdleTimeout(null);

        try {
            $process->start();
            $pid = $process->getPid();

            $target->update([
                'status'             => OutputTarget::STATUS_CONNECTING,
                'pid'                => $pid,
                'connected_at'       => now(),
                'last_error'         => null,
                'reconnect_attempts' => 0,
                'metadata'           => array_merge($target->metadata ?? [], [
                    'source_type' => $sourceType,
                    'source'      => $source,
                ]),
            ]);

            $this->logTarget($target, 'info', 'started',
                "Started [{$target->output_protocol}] via {$sourceType} → {$target->output_url}",
                ['pid' => $pid, 'source' => $sourceType]
            );

            Log::info('OutputTarget started', [
                'target'    => $target->id,
                'channel'   => $target->channel->slug,
                'protocol'  => $target->output_protocol,
                'source'    => $sourceType,
                'transcode' => $needsTranscode,
                'pid'       => $pid,
            ]);
        } catch (Exception $e) {
            $target->update([
                'status'       => OutputTarget::STATUS_ERROR,
                'last_error'   => $e->getMessage(),
                'last_error_at'=> now(),
            ]);
            $this->logTarget($target, 'error', 'start_failed', $e->getMessage());
        }
    }

    public function stopTarget(OutputTarget $target): void
    {
        $this->killTargetProcess($target);
        $target->update([
            'status'       => OutputTarget::STATUS_STOPPED,
            'pid'          => null,
            'connected_at' => null,
        ]);
        $this->logTarget($target, 'info', 'stopped', 'Output stopped');
    }

    public function startChannelOutputs(Channel $channel, string $context): void
    {
        $targets = $channel->outputTargets()
            ->where('is_enabled', true)
            ->whereIn('trigger', $this->triggersForContext($context))
            ->get();

        foreach ($targets as $target) {
            $this->startTarget($target);
        }

        Log::info("Started {$targets->count()} output(s) [{$context}]",
            ['channel' => $channel->slug]);
    }

    public function stopChannelOutputs(Channel $channel, ?string $context = null): void
    {
        $query = $channel->outputTargets()->whereIn('status', [
            OutputTarget::STATUS_CONNECTING,
            OutputTarget::STATUS_CONNECTED,
            OutputTarget::STATUS_RECONNECTING,
        ]);

        if ($context) {
            $query->whereIn('trigger', $this->triggersForContext($context));
        }

        foreach ($query->get() as $target) {
            $this->stopTarget($target);
        }
    }

    public function pushChannelToUrls(Channel $channel, array $destinations): array
    {
        $results = [];

        foreach ($destinations as $dest) {
            $url      = $this->sanitizeUrl($dest['url']);
            $protocol = $dest['protocol'] ?? $this->protocol->detect($url);

            $target = OutputTarget::create([
                'channel_id'         => $channel->id,
                'name'               => $dest['name'] ?? $url,
                'output_url'         => $url,
                'output_protocol'    => $protocol,
                'trigger'            => OutputTarget::TRIGGER_MANUAL,
                'video_codec'        => $dest['video_codec'] ?? 'copy',
                'audio_codec'        => $dest['audio_codec'] ?? 'copy',
                'video_bitrate_kbps' => $dest['video_bitrate_kbps'] ?? null,
                'audio_bitrate_kbps' => $dest['audio_bitrate_kbps'] ?? null,
                'resolution'         => $dest['resolution'] ?? null,
                'framerate'          => $dest['framerate'] ?? null,
                'srt_latency_ms'     => $dest['srt_latency_ms'] ?? 120,
                'srt_passphrase'     => $dest['srt_passphrase'] ?? null,
                'is_enabled'         => true,
            ]);

            $this->startTarget($target);
            $results[] = $target->fresh()->toStatusArray();
        }

        return $results;
    }

    public function pushChannelsToTargets(array $channelIds, array $targetIds): array
    {
        $results = [];

        foreach ($channelIds as $channelId) {
            $channel = Channel::find($channelId);
            if (!$channel) continue;

            $channelResults = [];
            foreach ($targetIds as $targetId) {
                $target = OutputTarget::where('id', $targetId)
                    ->where('channel_id', $channelId)
                    ->first();

                if (!$target) continue;

                $this->startTarget($target);
                $channelResults[] = $target->fresh()->toStatusArray();
            }

            $results[$channelId] = $channelResults;
        }

        return $results;
    }

    public function checkAndReconnect(OutputTarget $target): void
    {
        if (in_array($target->status, [
            OutputTarget::STATUS_IDLE,
            OutputTarget::STATUS_STOPPED,
            OutputTarget::STATUS_ERROR,
        ])) {
            return;
        }

        if ($target->isRunning()) {
            if ($target->status === OutputTarget::STATUS_CONNECTING) {
                $target->update(['status' => OutputTarget::STATUS_CONNECTED]);
            }
            return;
        }

        if ($target->reconnect_attempts >= self::MAX_RECONNECTS) {
            $target->update([
                'status'        => OutputTarget::STATUS_ERROR,
                'last_error'    => "Gave up after {$target->reconnect_attempts} reconnect attempts",
                'last_error_at' => now(),
            ]);
            $this->logTarget($target, 'error', 'max_reconnects',
                "Max reconnects ({$target->reconnect_attempts}) reached");
            return;
        }

        $attempts = $target->reconnect_attempts + 1;
        $target->update([
            'status'             => OutputTarget::STATUS_RECONNECTING,
            'reconnect_attempts' => $attempts,
        ]);

        $this->logTarget($target, 'warning', 'reconnecting',
            "Process died — reconnecting (attempt {$attempts} of " . self::MAX_RECONNECTS . ')');

        sleep(self::RECONNECT_DELAY);
        $this->startTarget($target);
    }

    public function checkAllTargets(): void
    {
        OutputTarget::whereIn('status', [
            OutputTarget::STATUS_CONNECTING,
            OutputTarget::STATUS_CONNECTED,
            OutputTarget::STATUS_RECONNECTING,
        ])->with('channel')->each(fn(OutputTarget $t) => $this->checkAndReconnect($t));
    }

    protected function buildCommand(OutputTarget $target, string $source, string $sourceType): array
    {
        $ffmpeg   = config('services.ffmpeg.path', '/usr/bin/ffmpeg');
        $loglevel = config('services.ffmpeg.log_level', 'warning');

        $cmd = [$ffmpeg, '-y', '-loglevel', $loglevel];

        if ($sourceType === 'pipe') {
            array_push($cmd,
                '-fflags', 'nobuffer',
                '-flags', 'low_delay',
                '-f', 'mpegts',
                '-i', $source
            );
        } else {
            array_push($cmd,
                '-re',
                '-fflags', 'nobuffer',
                '-reconnect', '1',
                '-reconnect_at_eof', '1',
                '-reconnect_streamed', '1',
                '-i', $source
            );
        }

        if (in_array($target->output_protocol, ['icecast', 'shoutcast'])) {
            return $this->buildIcecastCommand($cmd, $target, $ffmpeg, $loglevel, $source, $sourceType);
        }

        $vCodec = $target->video_codec ?: 'copy';
        array_push($cmd, '-c:v', $vCodec);

        if ($vCodec !== 'copy') {
            if ($target->video_bitrate_kbps) {
                array_push($cmd, '-b:v', $target->video_bitrate_kbps . 'k',
                    '-maxrate', $target->video_bitrate_kbps . 'k',
                    '-bufsize', ($target->video_bitrate_kbps * 2) . 'k');
            }
            if ($target->resolution) {
                array_push($cmd, '-vf', "scale={$target->resolution}");
            }
            if ($target->framerate) {
                array_push($cmd, '-r', (string) $target->framerate);
            }
            if (in_array($vCodec, ['libx264', 'libx265'])) {
                array_push($cmd, '-preset', 'veryfast', '-tune', 'zerolatency',
                    '-x264-params', 'nal-hrd=cbr:force-cfr=1');
            }
        }

        $aCodec = $target->audio_codec ?: 'copy';
        array_push($cmd, '-c:a', $aCodec);

        if ($aCodec !== 'copy') {
            array_push($cmd,
                '-b:a', ($target->audio_bitrate_kbps ?? 128) . 'k',
                '-ar', '44100',
                '-ac', '2'
            );
        }

        return $this->appendOutputFormat($cmd, $target);
    }

    protected function buildIcecastCommand(
        array $cmd, OutputTarget $target,
        string $ffmpeg, string $loglevel,
        string $source, string $sourceType
    ): array {
        $base = [$ffmpeg, '-y', '-loglevel', $loglevel];

        if ($sourceType === 'pipe') {
            array_push($base, '-fflags', 'nobuffer', '-f', 'mpegts', '-i', $source);
        } else {
            array_push($base, '-re', '-i', $source);
        }

        array_push($base,
            '-vn',
            '-c:a', 'libmp3lame',
            '-b:a', ($target->audio_bitrate_kbps ?? 128) . 'k',
            '-ar', '44100',
            '-ac', '2',
            '-f', 'mp3',
            $target->output_url
        );

        return $base;
    }

    protected function appendOutputFormat(array $cmd, OutputTarget $target): array
    {
        $url = $target->output_url;

        switch ($target->output_protocol) {
            case 'rtmp':
            case 'rtmps':
                array_push($cmd, '-f', 'flv', $url);
                break;

            case 'srt':
                if (!str_contains($url, 'latency=')) {
                    $sep  = str_contains($url, '?') ? '&' : '?';
                    $url .= "{$sep}latency={$target->srt_latency_ms}";
                }
                if ($target->srt_passphrase && !str_contains($url, 'passphrase=')) {
                    $url .= "&passphrase={$target->srt_passphrase}";
                }
                if (!str_contains($url, 'mode=')) {
                    $url .= '&mode=caller';
                }
                array_push($cmd, '-f', 'mpegts', $url);
                break;

            case 'mpeg_ts_udp':
                if (!str_contains($url, 'buffer_size=')) {
                    $sep  = str_contains($url, '?') ? '&' : '?';
                    $url .= "{$sep}buffer_size=65535";
                }
                array_push($cmd, '-f', 'mpegts', $url);
                break;

            case 'mpeg_ts_tcp':
                if (!str_starts_with($url, 'tcp://')) {
                    $url = 'tcp://' . $url;
                }
                array_push($cmd, '-f', 'mpegts', $url);
                break;

            case 'rtp':
                array_push($cmd, '-f', 'rtp', $url);
                break;

            case 'hls_push':
                array_push($cmd,
                    '-f', 'hls',
                    '-hls_time', '2',
                    '-hls_list_size', '5',
                    '-hls_flags', 'delete_segments+independent_segments',
                    '-method', 'PUT',
                    $url
                );
                break;

            case 'file':
                array_push($cmd, '-f', 'matroska', $url);
                break;

            default:
                array_push($cmd, '-f', 'flv', $url);
        }

        return $cmd;
    }

    protected function targetNeedsTranscode(OutputTarget $target): bool
    {
        if (in_array($target->output_protocol, ['icecast', 'shoutcast'])) {
            return true;
        }

        return ($target->video_codec && $target->video_codec !== 'copy')
            || ($target->audio_codec && $target->audio_codec !== 'copy')
            || !empty($target->resolution)
            || !empty($target->framerate);
    }

    protected function killTargetProcess(OutputTarget $target): void
    {
        $pid = $target->pid;
        if (!$pid || !is_int($pid) || $pid <= 0) return;

        if (function_exists('posix_kill')) {
            @posix_kill($pid, 15);
            usleep(400000);
            if (file_exists("/proc/{$pid}")) {
                @posix_kill($pid, 9);
            }
        } else {
            $safePid = escapeshellarg((string) $pid);
            @exec("kill -15 {$safePid} 2>/dev/null");
            usleep(400000);
            if (file_exists("/proc/{$pid}")) {
                @exec("kill -9 {$safePid} 2>/dev/null");
            }
        }
    }

    protected function logTarget(
        OutputTarget $target, string $level,
        string $event, string $message, array $context = []
    ): void {
        OutputTargetLog::create([
            'output_target_id' => $target->id,
            'level'            => $level,
            'event'            => $event,
            'message'          => $message,
            'context'          => $context ?: null,
        ]);
    }

    private function triggersForContext(string $context): array
    {
        return match ($context) {
            'live'     => [OutputTarget::TRIGGER_ALWAYS, OutputTarget::TRIGGER_LIVE_ONLY],
            'fallback' => [OutputTarget::TRIGGER_ALWAYS, OutputTarget::TRIGGER_FALLBACK_ONLY],
            default    => [OutputTarget::TRIGGER_ALWAYS],
        };
    }

    /**
     * Sanitize a URL for output targets.
     */
    protected function sanitizeUrl(string $url): string
    {
        $url = trim($url);
        $allowed = ['rtmp://', 'rtmps://', 'rtsp://', 'srt://', 'udp://', 'rtp://', 'tcp://', 'http://', 'https://', 'file://', 'icecast://', 'shout://', '/'];
        $valid = false;
        foreach ($allowed as $prefix) {
            if (str_starts_with(strtolower($url), $prefix)) {
                $valid = true;
                break;
            }
        }
        if (!$valid) {
            throw new \InvalidArgumentException('Invalid output URL format.');
        }
        return $url;
    }
}
