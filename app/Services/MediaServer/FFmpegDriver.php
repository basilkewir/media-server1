<?php

namespace App\Services\MediaServer;

use App\Models\Channel;
use App\Services\ProtocolDetector;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class FFmpegDriver implements MediaServerDriver
{
    public function __construct(protected ProtocolDetector $protocol) {}

    public function getName(): string { return 'FFmpeg (built-in)'; }

    public function startIngest(Channel $channel, string $sourceUrl, bool $loop = false): void
    {
        $this->stopIngest($channel);

        $ffmpeg    = config('services.ffmpeg.path', '/usr/bin/ffmpeg');
        $loglevel  = config('services.ffmpeg.log_level', 'warning');
        $outputDir = storage_path("streams/{$channel->slug}");

        if (!is_dir($outputDir)) mkdir($outputDir, 0755, true);

        $pipe = "{$outputDir}/live.ts";
        if (!file_exists($pipe) && function_exists('posix_mkfifo')) {
            posix_mkfifo($pipe, 0600);
        }

        $cmd = [$ffmpeg, '-y', '-loglevel', $loglevel];

        if ($loop) array_push($cmd, '-stream_loop', '-1');
        if ($loop || $this->protocol->detect($sourceUrl) === 'file') $cmd[] = '-re';

        foreach ($this->protocol->getInputArgs($sourceUrl) as $arg) $cmd[] = $arg;
        array_push($cmd, '-i', $sourceUrl);

        $hlsDur  = (string) config('services.stream.hls_segment_duration', 2);
        $hlsList = (string) config('services.stream.hls_segments_in_playlist', 10);

        $tee = implode('|', [
            "[f=mpegts:onfail=ignore]{$pipe}",
            "[f=hls:hls_time={$hlsDur}:hls_list_size={$hlsList}:hls_flags=delete_segments+append_list+independent_segments:hls_segment_type=mpegts:hls_segment_filename={$outputDir}/seg%05d.ts]{$outputDir}/playlist.m3u8",
        ]);

        array_push($cmd, '-c:v', 'copy', '-c:a', 'copy', '-f', 'tee', '-map', '0', $tee);

        $process = new Process($cmd);
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        $process->start();

        Cache::put("ingest_pid:{$channel->id}", $process->getPid(), now()->addHours(24));

        Log::info('FFmpeg ingest started', [
            'channel'  => $channel->slug,
            'pid'      => $process->getPid(),
            'protocol' => $this->protocol->label($sourceUrl),
            'loop'     => $loop,
        ]);
    }

    public function stopIngest(Channel $channel): void
    {
        $pid = Cache::get("ingest_pid:{$channel->id}");
        if ($pid) {
            $this->kill((int) $pid);
            Cache::forget("ingest_pid:{$channel->id}");
        }
        $pipe = storage_path("streams/{$channel->slug}/live.ts");
        if (file_exists($pipe)) @unlink($pipe);
    }

    public function isRunning(Channel $channel): bool
    {
        $pid = Cache::get("ingest_pid:{$channel->id}");
        return $pid && file_exists("/proc/{$pid}");
    }

    public function startOutput(Channel $channel, string $destUrl, array $options = []): string
    {
        $ffmpeg   = config('services.ffmpeg.path', '/usr/bin/ffmpeg');
        $loglevel = config('services.ffmpeg.log_level', 'warning');
        $pipe     = storage_path("streams/{$channel->slug}/live.ts");
        $hls      = storage_path("streams/{$channel->slug}/playlist.m3u8");

        $needsTranscode = !empty($options['video_codec']) && $options['video_codec'] !== 'copy'
                       || !empty($options['audio_codec']) && $options['audio_codec'] !== 'copy';

        $source = (!$needsTranscode && file_exists($pipe)) ? $pipe : $hls;
        $isPipe = $source === $pipe;

        $cmd = [$ffmpeg, '-y', '-loglevel', $loglevel];

        if ($isPipe) {
            array_push($cmd, '-fflags', 'nobuffer', '-flags', 'low_delay', '-f', 'mpegts', '-i', $source);
        } else {
            array_push($cmd, '-re', '-fflags', 'nobuffer', '-reconnect', '1', '-reconnect_at_eof', '1', '-i', $source);
        }

        $vCodec = $options['video_codec'] ?? 'copy';
        $aCodec = $options['audio_codec'] ?? 'copy';

        array_push($cmd, '-c:v', $vCodec);
        if ($vCodec !== 'copy') {
            if (!empty($options['video_bitrate_kbps'])) array_push($cmd, '-b:v', $options['video_bitrate_kbps'] . 'k');
            if (!empty($options['resolution']))         array_push($cmd, '-vf', "scale={$options['resolution']}");
            if (in_array($vCodec, ['libx264', 'libx265'])) array_push($cmd, '-preset', 'veryfast', '-tune', 'zerolatency');
        }

        array_push($cmd, '-c:a', $aCodec);
        if ($aCodec !== 'copy') {
            array_push($cmd, '-b:a', ($options['audio_bitrate_kbps'] ?? 128) . 'k', '-ar', '44100', '-ac', '2');
        }

        // Output format
        $proto = $options['output_protocol'] ?? 'rtmp';
        match ($proto) {
            'rtmp', 'rtmps'  => array_push($cmd, '-f', 'flv', $destUrl),
            'srt'            => array_push($cmd, '-f', 'mpegts', $destUrl . (str_contains($destUrl, '?') ? '&' : '?') . "latency={$options['srt_latency_ms']}"),
            'mpeg_ts_udp'    => array_push($cmd, '-f', 'mpegts', $destUrl),
            'mpeg_ts_tcp'    => array_push($cmd, '-f', 'mpegts', str_starts_with($destUrl, 'tcp://') ? $destUrl : "tcp://{$destUrl}"),
            'rtp'            => array_push($cmd, '-f', 'rtp', $destUrl),
            'icecast', 'shoutcast' => array_push($cmd, '-vn', '-c:a', 'libmp3lame', '-b:a', '128k', '-f', 'mp3', $destUrl),
            default          => array_push($cmd, '-f', 'flv', $destUrl),
        };

        $process = new Process($cmd);
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        $process->start();

        return (string) $process->getPid();
    }

    public function stopOutput(Channel $channel, string $handle): void
    {
        $this->kill((int) $handle);
    }

    public function getStats(Channel $channel): array
    {
        return [
            'driver'        => $this->getName(),
            'ingest_running'=> $this->isRunning($channel),
            'hls_available' => file_exists(storage_path("streams/{$channel->slug}/playlist.m3u8")),
            'pipe_available'=> file_exists(storage_path("streams/{$channel->slug}/live.ts")),
        ];
    }

    private function kill(int $pid): void
    {
        if (function_exists('posix_kill')) {
            @posix_kill($pid, 15); usleep(400000);
            if (file_exists("/proc/{$pid}")) @posix_kill($pid, 9);
        } else {
            @exec("kill -15 {$pid} 2>/dev/null"); usleep(400000);
            if (file_exists("/proc/{$pid}")) @exec("kill -9 {$pid} 2>/dev/null");
        }
    }
}
