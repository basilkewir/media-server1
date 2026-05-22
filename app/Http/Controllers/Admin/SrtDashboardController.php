<?php

namespace App\Http\Controllers\Admin;

use App\Models\SrtStream;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Services\StreamingService;

class SrtDashboardController extends Controller
{
    /**
     * Get SRT streams widget data for dashboard
     */
    public function widget()
    {
    $this->syncRuntimeStatsToDatabase();

        $streams = SrtStream::all();
        $activeStreams = SrtStream::where('enabled', true)->count();
        $totalStreams = $streams->count();
        $streamsData = [];

        foreach ($streams as $stream) {
            $streamsData[] = [
                'id' => $stream->id,
                'name' => $stream->name,
                'stream_id' => $stream->stream_id,
                'srt_port' => $stream->srt_port,
                'rtmp_stream' => $stream->rtmp_stream,
                'enabled' => $stream->enabled,
                'status' => $stream->status,
                'bitrate' => $stream->bitrate,
                'last_connected_at' => $stream->last_connected_at?->format('Y-m-d H:i:s'),
            ];
        }

        return response()->json([
            'total' => $totalStreams,
            'active' => $activeStreams,
            'streams' => $streamsData,
        ]);
    }

    /**
     * Get detailed SRT stream information
     */
    public function streamDetails($id)
    {
        $stream = SrtStream::findOrFail($id);

        return response()->json([
            'stream' => [
                'id' => $stream->id,
                'name' => $stream->name,
                'stream_id' => $stream->stream_id,
                'srt_port' => $stream->srt_port,
                'rtmp_stream' => $stream->rtmp_stream,
                'description' => $stream->description,
                'enabled' => $stream->enabled,
                'status' => $stream->status,
                'bitrate' => $stream->bitrate,
                'resolution' => $stream->resolution,
                'codec_video' => $stream->codec_video,
                'codec_audio' => $stream->codec_audio,
                'last_connected_at' => $stream->last_connected_at?->format('Y-m-d H:i:s'),
                'created_at' => $stream->created_at->format('Y-m-d H:i:s'),
                'srt_url' => "srt://localhost:{$stream->srt_port}?streamid={$stream->stream_id}",
                'rtmp_url' => "rtmp://localhost:1935/live/{$stream->rtmp_stream}",
                'hls_url' => "http://localhost/hls/{$stream->rtmp_stream}/index.m3u8",
                'dash_url' => "http://localhost/dash/{$stream->rtmp_stream}/manifest.mpd",
            ],
        ]);
    }

    /**
     * Get SRT stream logs
     */
    public function logs($id)
    {
        $stream = SrtStream::findOrFail($id);
        $logFile = storage_path("logs/srt-server.log");

        if (!file_exists($logFile)) {
            return response()->json(['logs' => []]);
        }

        $allLogs = array_reverse(file($logFile, FILE_IGNORE_NEW_LINES));
        $streamLogs = array_filter($allLogs, function ($log) use ($stream) {
            return strpos($log, $stream->stream_id) !== false
                || strpos($log, (string)$stream->srt_port) !== false;
        });

        return response()->json([
            'stream_id' => $stream->stream_id,
            'logs' => array_values(array_slice($streamLogs, 0, 100)), // Last 100 logs
        ]);
    }

    /**
     * Get current SRT status
     */
    public function status()
    {
    $this->syncRuntimeStatsToDatabase();

        $streams = SrtStream::all();
        $statusData = [];

        foreach ($streams as $stream) {
            // Check if process is running by checking port
            $isListening = $this->checkPortListening($stream->srt_port);

            $statusData[] = [
                'id' => $stream->id,
                'name' => $stream->name,
                'stream_id' => $stream->stream_id,
                'port' => $stream->srt_port,
                'status' => $stream->status,
                'enabled' => $stream->enabled,
                'listening' => $isListening,
                'last_connected_at' => $stream->last_connected_at,
            ];
        }

        return response()->json([
            'streams' => $statusData,
            'timestamp' => now(),
        ]);
    }

    /**
     * Check if SRT port is listening
     */
    private function checkPortListening($port)
    {
        $output = shell_exec("ss -tlnup 2>/dev/null | grep :{$port}");
        return !empty($output);
    }

    /**
     * Best-effort sync of live runtime stats (status, last connected, bitrate) into DB.
     *
     * Data sources:
     * - /var/www/mediaserver/storage/logs/srt-server.log (FFmpeg logger output)
     * - Port listening check (ss)
     *
     * This keeps the UI accurate without requiring manual DB updates.
     */
    private function syncRuntimeStatsToDatabase(): void
    {
        try {
            $logFile = storage_path('logs/srt-server.log');
            $logText = '';

            if (file_exists($logFile)) {
                // Avoid loading unbounded logs.
                $logText = (string) @file_get_contents($logFile, false, null, 0, 1024 * 1024);
                if ($logText === false) {
                    $logText = '';
                }
            }

            $streams = SrtStream::all();
            foreach ($streams as $stream) {
                $isListening = $this->checkPortListening($stream->srt_port);

                // Parse latest bitrate(s) from logs if present.
                $bitrateKbps = null;
                $videoKbps = null;
                $audioKbps = null;
                if ($logText !== '') {
                    $pattern = "/\\[FFmpeg-" . preg_quote($stream->stream_id, '/') . "\\].*?bitrate=(\\d+(?:\\.\\d+)?)kbits\\/s/is";
                    if (preg_match_all($pattern, $logText, $m) && !empty($m[1])) {
                        $bitrateKbps = (int) round((float) end($m[1]));
                    }

                    // Optional: some FFmpeg outputs include separate video/audio bitrate info.
                    // Patterns we try (best-effort): "video:(...)kbits/s" and "audio:(...)kbits/s".
                    $vPattern = "/\\[FFmpeg-" . preg_quote($stream->stream_id, '/') . "\\].*?video:\s*(\\d+(?:\\.\\d+)?)kbits\\/s/is";
                    if (preg_match_all($vPattern, $logText, $vm) && !empty($vm[1])) {
                        $videoKbps = (int) round((float) end($vm[1]));
                    }

                    $aPattern = "/\\[FFmpeg-" . preg_quote($stream->stream_id, '/') . "\\].*?audio:\s*(\\d+(?:\\.\\d+)?)kbits\\/s/is";
                    if (preg_match_all($aPattern, $logText, $am) && !empty($am[1])) {
                        $audioKbps = (int) round((float) end($am[1]));
                    }
                }

                $dirty = false;

                // If port is listening, consider it connected (daemon running for that port).
                $newStatus = $isListening ? 'connected' : 'disconnected';
                if ($stream->status !== $newStatus) {
                    $stream->status = $newStatus;
                    $dirty = true;

                    if ($newStatus === 'connected') {
                        $stream->last_connected_at = now();
                        $dirty = true;
                    } elseif ($newStatus === 'disconnected' && $stream->vod_fallback_enabled && $stream->channel_id) {
                        // Trigger VOD fallback when SRT stream disconnects and it has VOD fallback enabled
                        try {
                            $channel = $stream->channel;
                            if ($channel && $channel->vod_playlist_url) {
                                app(StreamingService::class)->switchToVODFallback($channel);
                                Log::info("SRT stream disconnected; switched to VOD fallback", [
                                    'srt_stream' => $stream->name,
                                    'channel' => $channel->slug,
                                ]);
                            }
                        } catch (\Throwable $e) {
                            Log::warning("Failed to trigger VOD fallback for SRT stream", [
                                'srt_stream' => $stream->name,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }

                if ($bitrateKbps !== null && $bitrateKbps > 0 && (int) $stream->bitrate !== $bitrateKbps) {
                    $stream->bitrate = $bitrateKbps;
                    $dirty = true;
                }

                // If we managed to parse video/audio bitrate, expose it via error_log as a lightweight
                // field the UI can display without schema changes.
                if ($videoKbps !== null || $audioKbps !== null) {
                    $parts = [];
                    if ($videoKbps !== null) {
                        $parts[] = "video={$videoKbps}kbps";
                    }
                    if ($audioKbps !== null) {
                        $parts[] = "audio={$audioKbps}kbps";
                    }
                    $note = 'bitrate_parts:' . implode(',', $parts);

                    if ($stream->error_log !== $note) {
                        $stream->error_log = $note;
                        $dirty = true;
                    }
                }

                if ($dirty) {
                    $stream->save();
                }
            }
        } catch (\Throwable $e) {
            // Never break the dashboard due to stats parsing.
            Log::warning('SRT dashboard runtime stats sync failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
