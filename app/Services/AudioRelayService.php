<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Channel;
use App\Models\RelayServer;
use App\Models\StreamEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class AudioRelayService
{
    const CACHE_KEY_PREFIX = 'audio_relay_pid:';

    public function __construct(
        protected IcecastService $icecast,
    ) {}

    /**
     * Start audio-only relay to an Icecast/Shoutcast server.
     * Used when video stream is down but audio content should keep playing.
     */
    public function startAudioRelay(Channel $channel): ?int
    {
        $this->stopAudioRelay($channel);

        $sourceUrl = $this->resolveAudioSource($channel);

        if (!$sourceUrl) {
            Log::warning('No audio source available for relay', ['channel' => $channel->slug]);
            return null;
        }

        $targetUrl = $channel->audio_relay_target_url;

        if (!$targetUrl && $channel->is_icecast_enabled) {
            $password = $this->icecast->getPassword($channel);
            $mount    = $this->icecast->getMountPoint($channel) ?? "/{$channel->slug}";
            $host     = config('services.icecast.host', 'localhost');
            $port     = config('services.icecast.port', 8000);
            $targetUrl = "icecast://source:{$password}@{$host}:{$port}{$mount}";
        }

        if (!$targetUrl) {
            Log::warning('No audio relay target URL for channel', ['channel' => $channel->slug]);
            return null;
        }

        $ffmpeg   = config('services.ffmpeg.path', '/usr/bin/ffmpeg');
        $loglevel = config('services.ffmpeg.log_level', 'warning');
        $bitrate  = $channel->bitrate_kbps ?? 128;

        $cmd = [
            $ffmpeg, '-y', '-loglevel', $loglevel,
            '-stream_loop', '-1',
            '-re',
            '-i', $sourceUrl,
            '-vn',
            '-c:a', 'libmp3lame',
            '-b:a', $bitrate . 'k',
            '-ar', '44100',
            '-ac', '2',
            '-f', 'mp3',
            $targetUrl,
        ];

        $process = new Process($cmd);
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        $process->start();

        $pid = $process->getPid();

        if ($pid) {
            Cache::put(self::CACHE_KEY_PREFIX . $channel->id, $pid, now()->addHours(24));
            Log::info('Audio relay started', [
                'channel'  => $channel->slug,
                'pid'      => $pid,
                'source'   => $sourceUrl,
                'target'   => $targetUrl,
                'bitrate'  => $bitrate,
            ]);

            StreamEvent::create([
                'channel_id' => $channel->id,
                'event_type' => 'audio_relay_started',
                'message'    => "Audio relay started to {$targetUrl}",
                'severity'   => StreamEvent::SEVERITY_INFO,
            ]);
        }

        return $pid;
    }

    /**
     * Resolve the best audio source in order of priority:
     * 1. Channel's audio_source_url
     * 2. Channel's audio_relay_playlist_url (M3U8 of audio files)
     * 3. Channel's VOD playlist (using audio stream only)
     * 4. Built-in silent/ambient source
     */
    protected function resolveAudioSource(Channel $channel): ?string
    {
        if ($channel->audio_source_url) {
            return $channel->audio_source_url;
        }

        if ($channel->audio_relay_playlist_url) {
            return $channel->audio_relay_playlist_url;
        }

        if ($channel->vod_playlist_url) {
            return $channel->vod_playlist_url;
        }

        return null;
    }

    /**
     * Stop an audio relay process for a channel.
     */
    public function stopAudioRelay(Channel $channel): void
    {
        $pid = Cache::get(self::CACHE_KEY_PREFIX . $channel->id);

        if ($pid && is_int($pid) && $pid > 0) {
            $this->killProcess($pid);
            Cache::forget(self::CACHE_KEY_PREFIX . $channel->id);

            Log::info('Audio relay stopped', ['channel' => $channel->slug, 'pid' => $pid]);

            StreamEvent::create([
                'channel_id' => $channel->id,
                'event_type' => 'audio_relay_stopped',
                'message'    => 'Audio relay stopped',
                'severity'   => StreamEvent::SEVERITY_INFO,
            ]);
        }
    }

    /**
     * Check if audio relay process is running for a channel.
     */
    public function isAudioRelayActive(Channel $channel): bool
    {
        $pid = Cache::get(self::CACHE_KEY_PREFIX . $channel->id);
        return $pid && is_int($pid) && file_exists("/proc/{$pid}");
    }

    /**
     * Get audio relay information for a channel.
     */
    public function getAudioRelayInfo(Channel $channel): array
    {
        $pid      = Cache::get(self::CACHE_KEY_PREFIX . $channel->id);
        $running  = $pid && is_int($pid) && file_exists("/proc/{$pid}");

        return [
            'active'       => $running,
            'pid'          => $pid,
            'source_url'   => $channel->audio_source_url ?: $channel->audio_relay_playlist_url,
            'target_url'   => $channel->audio_relay_target_url,
            'bitrate_kbps' => $channel->bitrate_kbps ?? 128,
            'protocol'     => $channel->audio_relay_protocol ?? 'icecast',
        ];
    }

    /**
     * Forward audio to an external relay server (audio-only).
     */
    public function relayAudioToServer(Channel $channel, RelayServer $server): ?int
    {
        $sourceUrl = $this->resolveAudioSource($channel);

        if (!$sourceUrl) {
            return null;
        }

        $ffmpeg   = config('services.ffmpeg.path', '/usr/bin/ffmpeg');
        $loglevel = config('services.ffmpeg.log_level', 'warning');
        $bitrate  = $channel->bitrate_kbps ?? 128;

        $relayUrl = $this->buildRelayUrl($server, $channel);

        $cmd = [
            $ffmpeg, '-y', '-loglevel', $loglevel,
            '-stream_loop', '-1', '-re',
            '-i', $sourceUrl,
            '-vn',
            '-c:a', 'libmp3lame',
            '-b:a', $bitrate . 'k',
            '-ar', '44100', '-ac', '2',
            '-f', match ($server->server_type) {
                'shoutcast' => 'mp3',
                default     => 'mp3',
            },
            $relayUrl,
        ];

        $process = new Process($cmd);
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        $process->start();

        $pid = $process->getPid();

        Log::info("Audio forwarded to relay", [
            'channel' => $channel->slug,
            'server'  => $server->name,
            'pid'     => $pid,
        ]);

        return $pid;
    }

    /**
     * Forward video+audio to an external RTMP server.
     * This is an enhanced version that works independently of outputs.
     */
    public function forwardStreamToServer(Channel $channel, RelayServer $server): ?int
    {
        $hlsPlaylist = storage_path("streams/{$channel->slug}/playlist.m3u8");

        if (!file_exists($hlsPlaylist)) {
            Log::warning('No HLS output for channel forwarding', ['channel' => $channel->slug]);
            return null;
        }

        $ffmpeg   = config('services.ffmpeg.path', '/usr/bin/ffmpeg');
        $loglevel = config('services.ffmpeg.log_level', 'warning');

        $relayUrl = "rtmp://{$server->hostname}:{$server->port}/live/{$channel->slug}";

        if ($server->username && $server->password) {
            $relayUrl = "rtmp://{$server->username}:{$server->password}@{$server->hostname}:{$server->port}/live/{$channel->slug}";
        }

        $cmd = [
            $ffmpeg, '-y', '-loglevel', $loglevel,
            '-re', '-i', $hlsPlaylist,
            '-c:v', 'copy', '-c:a', 'aac',
            '-f', 'flv', $relayUrl,
        ];

        $process = new Process($cmd);
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        $process->start();

        $pid = $process->getPid();

        Log::info("Stream forwarded to server", [
            'channel' => $channel->slug,
            'server'  => $server->name,
            'url'     => $relayUrl,
            'pid'     => $pid,
        ]);

        return $pid;
    }

    /**
     * Monitor and restart audio relay if process died.
     */
    public function monitorAudioRelays(): void
    {
        Channel::where('audio_relay_enabled', true)
            ->orWhere('audio_fallback_enabled', true)
            ->each(function (Channel $channel) {
                $active = $channel->activeStream();
                $isOff = !$active || $active->status === 'completed';

                if ($isOff && $channel->audio_fallback_enabled) {
                    if (!$this->isAudioRelayActive($channel)) {
                        Log::info('Auto-starting audio fallback relay', ['channel' => $channel->slug]);
                        $this->startAudioRelay($channel);
                    }
                } elseif (!$isOff && $channel->audio_fallback_enabled) {
                    $this->stopAudioRelay($channel);
                }
            });
    }

    /**
     * Build relay URL for audio or video forwarding.
     */
    public function buildRelayUrl(RelayServer $server, Channel $channel): string
    {
        $user  = $server->username;
        $pass  = $server->password;
        $host  = $server->hostname;
        $port  = $server->port;
        $mount = $channel->metadata['icecast']['mount_point'] ?? "/{$channel->slug}";

        return match ($server->server_type) {
            'icecast'   => "icecast://{$user}:{$pass}@{$host}:{$port}{$mount}",
            'shoutcast' => "shout://{$user}:{$pass}@{$host}:{$port}/",
            'rtmp'      => "rtmp://{$host}:{$port}/live/{$channel->slug}",
            default     => "http://{$host}:{$port}/{$channel->slug}",
        };
    }

    /**
     * Kill a process by PID safely (cross-platform).
     */
    protected function killProcess(int $pid): void
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
            $output = null; $code = 0;
            exec("kill -15 {$safePid} 2>&1", $output, $code);
            if ($code !== 0) {
                Log::warning('Failed to send SIGTERM to process', ['pid' => $pid]);
            }
            usleep(500000);
            if (file_exists("/proc/{$pid}")) {
                $output2 = null; $code2 = 0;
                exec("kill -9 {$safePid} 2>&1", $output2, $code2);
                Log::warning('Force-killed unresponsive process', ['pid' => $pid]);
            }
        }
    }
}
