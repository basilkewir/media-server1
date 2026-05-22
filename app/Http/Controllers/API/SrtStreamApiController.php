<?php

namespace App\Http\Controllers\API;

use App\Models\SrtStream;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

/**
 * API for SRT Stream Management
 * Provides endpoints for creating, managing, and monitoring SRT streams
 */
class SrtStreamApiController extends Controller
{
    /**
     * Get all SRT streams with status
     */
    public function index(): JsonResponse
    {
        try {
            $streams = SrtStream::all();

            return response()->json([
                'success' => true,
                'data' => $streams->map(fn($s) => $this->formatStream($s)),
                'total' => $streams->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get specific stream details
     */
    public function show(SrtStream $stream): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->formatStream($stream),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new SRT stream
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|unique:srt_streams,name|max:255',
                'rtmp_stream' => 'required|string|unique:srt_streams,rtmp_stream|max:255',
                'description' => 'nullable|string|max:1000',
                'bitrate' => 'nullable|integer|min:100|max:50000',
                'resolution' => 'nullable|string',
                'codec_video' => 'nullable|string',
                'codec_audio' => 'nullable|string',
            ]);

            $stream = new SrtStream();
            $stream->name = $validated['name'];
            $stream->stream_id = SrtStream::generateStreamId($validated['name']);
            $stream->srt_port = SrtStream::getNextAvailablePort();
            $stream->rtmp_stream = $validated['rtmp_stream'];
            $stream->description = $validated['description'] ?? null;
            $stream->bitrate = $validated['bitrate'] ?? 1500;
            $stream->resolution = $validated['resolution'] ?? '720p';
            $stream->codec_video = $validated['codec_video'] ?? 'h264';
            $stream->codec_audio = $validated['codec_audio'] ?? 'aac';
            $stream->enabled = true;
            $stream->status = 'pending';

            $stream->save();

            // Create Flussonic stream and update SRT config
            shell_exec("cd /var/www/mediaserver && php artisan srt:create-stream {$stream->id} 2>&1");

            Log::info("SRT Stream created via API: {$stream->name} on port {$stream->srt_port}");

            return response()->json([
                'success' => true,
                'message' => "Stream '{$stream->name}' created on port {$stream->srt_port}",
                'data' => $this->formatStream($stream),
            ], 201);
        } catch (\Exception $e) {
            Log::error("Error creating SRT stream: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update SRT stream
     */
    public function update(Request $request, SrtStream $stream): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => "required|string|unique:srt_streams,name,{$stream->id}|max:255",
                'description' => 'nullable|string|max:1000',
                'bitrate' => 'nullable|integer|min:100|max:50000',
                'resolution' => 'nullable|string',
                'codec_video' => 'nullable|string',
                'codec_audio' => 'nullable|string',
            ]);

            $stream->update($validated);

            // Update SRT config
            shell_exec("pkill -USR1 srt-daemon 2>/dev/null || true");

            Log::info("SRT Stream updated via API: {$stream->name}");

            return response()->json([
                'success' => true,
                'message' => 'Stream updated successfully',
                'data' => $this->formatStream($stream),
            ]);
        } catch (\Exception $e) {
            Log::error("Error updating SRT stream: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Toggle stream enabled/disabled
     */
    public function toggle(SrtStream $stream): JsonResponse
    {
        try {
            $stream->enabled = !$stream->enabled;
            $stream->save();

            // Signal daemon to reload
            shell_exec("pkill -USR1 srt-daemon 2>/dev/null || true");

            $status = $stream->enabled ? 'enabled' : 'disabled';
            Log::info("SRT Stream toggled via API: {$stream->name} is now {$status}");

            return response()->json([
                'success' => true,
                'message' => "Stream {$status} successfully",
                'data' => $this->formatStream($stream),
            ]);
        } catch (\Exception $e) {
            Log::error("Error toggling SRT stream: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete SRT stream
     */
    public function destroy(SrtStream $stream): JsonResponse
    {
        try {
            $name = $stream->name;
            $port = $stream->srt_port;

            // Delete via artisan command
            shell_exec("cd /var/www/mediaserver && php artisan srt:delete-stream {$stream->id} 2>&1");

            Log::info("SRT Stream deleted via API: {$name} (port {$port})");

            return response()->json([
                'success' => true,
                'message' => 'Stream deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error("Error deleting SRT stream: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get stream statistics
     */
    public function stats(SrtStream $stream): JsonResponse
    {
        try {
            $logPath = '/var/www/mediaserver/storage/logs/srt-server.log';
            $stats = ['bitrate' => 'N/A', 'speed' => 'N/A', 'status' => $stream->status];

            if (file_exists($logPath)) {
                $logs = file_get_contents($logPath);
                preg_match_all("/\[FFmpeg-{$stream->stream_id}\].*bitrate=(\d+\.\d+)kbits\/s.*speed=(\d+\.?\d*)x/", $logs, $matches);

                if (!empty($matches[1])) {
                    $stats['bitrate'] = end($matches[1]) . ' kbps';
                    $stats['speed'] = end($matches[2]) . 'x';
                }
            }

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get stream logs
     */
    public function logs(SrtStream $stream, Request $request): JsonResponse
    {
        try {
            $lines = $request->get('lines', 50);
            $logPath = '/var/www/mediaserver/storage/logs/srt-server.log';

            $logs = [];
            if (file_exists($logPath)) {
                $handle = fopen($logPath, 'r');
                $allLines = [];

                while (($line = fgets($handle)) !== false) {
                    if (strpos($line, $stream->stream_id) !== false) {
                        $allLines[] = trim($line);
                    }
                }
                fclose($handle);

                $logs = array_slice($allLines, -$lines);
            }

            return response()->json([
                'success' => true,
                'data' => $logs,
                'total' => count($logs),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get recommended next port
     */
    public function nextPort(): JsonResponse
    {
        try {
            $port = SrtStream::getNextAvailablePort();

            return response()->json([
                'success' => true,
                'port' => $port,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Format stream response
     */
    private function formatStream(SrtStream $stream): array
    {
        return [
            'id' => $stream->id,
            'name' => $stream->name,
            'stream_id' => $stream->stream_id,
            'srt_port' => $stream->srt_port,
            'rtmp_stream' => $stream->rtmp_stream,
            'description' => $stream->description,
            'enabled' => $stream->enabled,
            'bitrate' => $stream->bitrate,
            'resolution' => $stream->resolution,
            'codec_video' => $stream->codec_video,
            'codec_audio' => $stream->codec_audio,
            'status' => $stream->status,
            'last_connected_at' => $stream->last_connected_at,
            'error_log' => $stream->error_log,
            'srt_url' => "srt://" . config('app.server_ip', 'server') . ":{$stream->srt_port}?streamid={$stream->stream_id}",
            'rtmp_url' => "rtmp://127.0.0.1:1935/{$stream->rtmp_stream}",
            'hls_url' => "http://" . config('app.server_ip', 'server') . "/{$stream->rtmp_stream}/index.m3u8",
            'dash_url' => "http://" . config('app.server_ip', 'server') . "/{$stream->rtmp_stream}/manifest.mpd",
        ];
    }
}
