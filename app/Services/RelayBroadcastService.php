<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Channel;
use App\Models\RelayServer;
use App\Models\RelayBroadcast;
use App\Models\RelayBroadcastLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Exception;

class RelayBroadcastService
{
    public function startRelay(Channel $channel, RelayServer $relayServer): RelayBroadcast
    {
        $existing = RelayBroadcast::where('channel_id', $channel->id)
            ->where('relay_server_id', $relayServer->id)
            ->where('is_active', true)
            ->first();

        if ($existing) {
            $this->stopRelay($existing);
        }

        return DB::transaction(function () use ($channel, $relayServer) {
            $relay = RelayBroadcast::create([
                'channel_id'      => $channel->id,
                'relay_server_id' => $relayServer->id,
                'status'          => 'connecting',
                'is_active'       => true,
                'bitrate_kbps'    => $channel->bitrate_kbps ?? 128,
            ]);

            try {
                $this->startRelayProcess($channel, $relayServer, $relay);
            } catch (\Exception $e) {
                $relay->update(['status' => 'error', 'is_active' => false]);
                RelayBroadcastLog::create([
                    'relay_broadcast_id' => $relay->id,
                    'event_type'         => 'relay_failed',
                    'message'            => "Failed to start relay: {$e->getMessage()}",
                    'status'             => 'error',
                ]);
                throw $e;
            }

            RelayBroadcastLog::create([
                'relay_broadcast_id' => $relay->id,
                'event_type'         => 'relay_started',
                'message'            => "Relay started to {$relayServer->name}",
                'status'             => 'success',
            ]);

            return $relay->fresh();
        });
    }

    protected function startRelayProcess(Channel $channel, RelayServer $relayServer, RelayBroadcast $relay): void
    {
        $ffmpegPath = config('services.ffmpeg.path', '/usr/bin/ffmpeg');

        $hlsPlaylist = storage_path("streams/{$channel->slug}/playlist.m3u8");

        if (!file_exists($hlsPlaylist)) {
            throw new Exception("No HLS output found for channel {$channel->slug}. Start the stream first.");
        }

        $relayUrl = $this->buildRelayUrl($relayServer, $channel);

        $command = [
            $ffmpegPath,
            '-re',
            '-i', $hlsPlaylist,
        ];

        if ($relayServer->server_type === 'icecast' || $relayServer->server_type === 'shoutcast') {
            $command = array_merge($command, [
                '-vn',
                '-c:a', 'libmp3lame',
                '-b:a', ($channel->bitrate_kbps ?? 128) . 'k',
                '-f', 'mp3',
                $relayUrl,
            ]);
        } else {
            $command = array_merge($command, [
                '-c:v', 'copy',
                '-c:a', 'aac',
                '-f', 'flv',
                $relayUrl,
            ]);
        }

        $process = new Process($command);
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        $process->start();

        $pid = $process->getPid();

        $relay->update([
            'status'    => 'connected',
            'relay_url' => $relayUrl,
            'metadata'  => array_merge($relay->metadata ?? [], ['relay_process_pid' => $pid]),
        ]);

        Log::info("Relay FFmpeg started", ['relay_id' => $relay->id, 'pid' => $pid, 'url' => $relayUrl]);
    }

    protected function buildRelayUrl(RelayServer $relayServer, Channel $channel): string
    {
        $user  = $relayServer->username;
        $pass  = $relayServer->password;
        $host  = $relayServer->hostname;
        $port  = $relayServer->port;
        $mount = $channel->metadata['icecast']['mount_point'] ?? "/{$channel->slug}";

        return match ($relayServer->server_type) {
            'icecast'   => "icecast://{$user}:{$pass}@{$host}:{$port}{$mount}",
            'shoutcast' => "shout://{$user}:{$pass}@{$host}:{$port}/",
            'rtmp'      => "rtmp://{$host}:{$port}/live/{$channel->slug}",
            default     => throw new Exception("Unknown relay server type: {$relayServer->server_type}"),
        };
    }

    public function stopRelay(RelayBroadcast $relay): bool
    {
        $pid = $relay->metadata['relay_process_pid'] ?? null;

        if ($pid && is_int($pid) && $pid > 0) {
            if (function_exists('posix_kill')) {
                @posix_kill($pid, 15);
                usleep(300000);
                if (file_exists("/proc/{$pid}")) {
                    @posix_kill($pid, 9);
                }
            } else {
                $safePid = escapeshellarg((string) $pid);
                $output = null; $code = 0;
                exec("kill -15 {$safePid} 2>&1", $output, $code);
                if ($code !== 0) {
                    Log::warning('Failed to send SIGTERM to relay process', ['pid' => $pid]);
                }
                usleep(300000);
                if (file_exists("/proc/{$pid}")) {
                    exec("kill -9 {$safePid} 2>&1");
                    Log::warning('Force-killed unresponsive relay process', ['pid' => $pid]);
                }
            }
        }

        $relay->update(['status' => 'stopped', 'is_active' => false]);

        RelayBroadcastLog::create([
            'relay_broadcast_id' => $relay->id,
            'event_type'         => 'relay_stopped',
            'message'            => 'Relay stopped',
            'status'             => 'success',
        ]);

        return true;
    }

    public function checkRelayHealth(RelayBroadcast $relay): bool
    {
        if (!$relay->relayServer->isOnline()) {
            $relay->update(['status' => 'server_offline', 'is_active' => false]);
            RelayBroadcastLog::create([
                'relay_broadcast_id' => $relay->id,
                'event_type'         => 'server_offline',
                'message'            => 'Relay server offline',
                'status'             => 'error',
            ]);
            return false;
        }

        $pid = $relay->metadata['relay_process_pid'] ?? null;

        if (!$pid || !is_int($pid) || !file_exists("/proc/{$pid}")) {
            $relay->update(['status' => 'process_died', 'is_active' => false]);
            RelayBroadcastLog::create([
                'relay_broadcast_id' => $relay->id,
                'event_type'         => 'process_died',
                'message'            => 'FFmpeg relay process died',
                'status'             => 'error',
            ]);
            return false;
        }

        $relay->update(['status' => 'connected']);
        return true;
    }

    public function getRelayStats(RelayBroadcast $relay): array
    {
        $pid     = $relay->metadata['relay_process_pid'] ?? null;
        $running = $pid && is_int($pid) && file_exists("/proc/{$pid}");

        return [
            'relay_id'      => $relay->id,
            'channel_id'    => $relay->channel_id,
            'status'        => $relay->status,
            'server_name'   => $relay->relayServer->name,
            'server_type'   => $relay->relayServer->server_type,
            'relay_url'     => $relay->relay_url,
            'is_active'     => $relay->is_active,
            'process_alive' => $running,
            'listeners'     => $relay->listeners,
            'bitrate_kbps'  => $relay->bitrate_kbps,
            'duration'      => $relay->created_at ? now()->diffInSeconds($relay->created_at) : 0,
        ];
    }

    public function getChannelRelays(Channel $channel): \Illuminate\Database\Eloquent\Collection
    {
        return $channel->relays()->where('is_active', true)->with('relayServer')->get();
    }

    public function checkAllRelayHealth(): void
    {
        RelayBroadcast::where('is_active', true)->each(fn($relay) => $this->checkRelayHealth($relay));
    }
}
