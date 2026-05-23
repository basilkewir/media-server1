<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Models\SrtStream;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class SrtStreamApiController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        try {
            $streams = SrtStream::all();

            return $this->success(
                data: $streams->map(fn($s) => $this->formatStream($s)),
                message: 'SRT streams retrieved successfully.',
            );
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage(), 'SRT_INDEX_FAILED');
        }
    }

    public function show(SrtStream $stream): JsonResponse
    {
        try {
            return $this->success(
                data: $this->formatStream($stream),
                message: 'SRT stream retrieved successfully.',
            );
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage(), 'SRT_SHOW_FAILED');
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name'        => 'required|string|unique:srt_streams,name|max:255',
                'rtmp_stream' => 'required|string|unique:srt_streams,rtmp_stream|max:255',
                'description' => 'nullable|string|max:1000',
                'bitrate'     => 'nullable|integer|min:100|max:50000',
                'resolution'  => 'nullable|string',
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

            $this->runArtisanCommand("srt:create-stream {$stream->id}");

            Log::info("SRT Stream created via API: {$stream->name} on port {$stream->srt_port}");

            return $this->success(
                data: $this->formatStream($stream),
                message: "Stream '{$stream->name}' created on port {$stream->srt_port}",
                statusCode: 201
            );
        } catch (\Exception $e) {
            Log::error("Error creating SRT stream: " . $e->getMessage());
            return $this->serverError($e->getMessage(), 'SRT_CREATE_FAILED');
        }
    }

    public function update(Request $request, SrtStream $stream): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name'        => "required|string|unique:srt_streams,name,{$stream->id}|max:255",
                'description' => 'nullable|string|max:1000',
                'bitrate'     => 'nullable|integer|min:100|max:50000',
                'resolution'  => 'nullable|string',
                'codec_video' => 'nullable|string',
                'codec_audio' => 'nullable|string',
            ]);

            $stream->update($validated);
            $this->signalDaemonReload();

            Log::info("SRT Stream updated via API: {$stream->name}");

            return $this->success(
                data: $this->formatStream($stream),
                message: 'Stream updated successfully',
            );
        } catch (\Exception $e) {
            Log::error("Error updating SRT stream: " . $e->getMessage());
            return $this->serverError($e->getMessage(), 'SRT_UPDATE_FAILED');
        }
    }

    public function toggle(SrtStream $stream): JsonResponse
    {
        try {
            $stream->enabled = !$stream->enabled;
            $stream->save();

            $this->signalDaemonReload();

            $status = $stream->enabled ? 'enabled' : 'disabled';
            Log::info("SRT Stream toggled via API: {$stream->name} is now {$status}");

            return $this->success(
                data: $this->formatStream($stream),
                message: "Stream {$status} successfully",
            );
        } catch (\Exception $e) {
            Log::error("Error toggling SRT stream: " . $e->getMessage());
            return $this->serverError($e->getMessage(), 'SRT_TOGGLE_FAILED');
        }
    }

    public function destroy(SrtStream $stream): JsonResponse
    {
        try {
            $name = $stream->name;
            $port = $stream->srt_port;

            $this->runArtisanCommand("srt:delete-stream {$stream->id}");

            Log::info("SRT Stream deleted via API: {$name} (port {$port})");

            return $this->success(message: 'Stream deleted successfully');
        } catch (\Exception $e) {
            Log::error("Error deleting SRT stream: " . $e->getMessage());
            return $this->serverError($e->getMessage(), 'SRT_DELETE_FAILED');
        }
    }

    public function stats(SrtStream $stream): JsonResponse
    {
        try {
            $logPath = storage_path('logs/srt-server.log');
            $stats = ['bitrate' => 'N/A', 'speed' => 'N/A', 'status' => $stream->status];

            if (file_exists($logPath)) {
                $logs = file_get_contents($logPath);
                preg_match_all("/\[FFmpeg-{$stream->stream_id}\].*bitrate=(\d+\.\d+)kbits\/s.*speed=(\d+\.?\d*)x/", $logs, $matches);

                if (!empty($matches[1])) {
                    $stats['bitrate'] = end($matches[1]) . ' kbps';
                    $stats['speed'] = end($matches[2]) . 'x';
                }
            }

            return $this->success(data: $stats, message: 'SRT stream stats retrieved.');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage(), 'SRT_STATS_FAILED');
        }
    }

    public function logs(SrtStream $stream, Request $request): JsonResponse
    {
        try {
            $lines = $request->get('lines', 50);
            $logPath = storage_path('logs/srt-server.log');

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

            return $this->success(data: $logs, message: 'SRT logs retrieved.');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage(), 'SRT_LOGS_FAILED');
        }
    }

    public function nextPort(): JsonResponse
    {
        try {
            $port = SrtStream::getNextAvailablePort();

            return $this->success(data: ['port' => $port], message: 'Next port retrieved.');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage(), 'SRT_PORT_FAILED');
        }
    }

    private function runArtisanCommand(string $args): void
    {
        $basePath = base_path();
        $php = PHP_BINARY ?: 'php';

        $process = new Process([$php, 'artisan', ...explode(' ', $args)]);
        $process->setWorkingDirectory($basePath);
        $process->setTimeout(30);
        $process->run();

        if (!$process->isSuccessful()) {
            Log::warning("Artisan command failed", [
                'command' => $args,
                'error' => $process->getErrorOutput(),
            ]);
        }
    }

    private function signalDaemonReload(): void
    {
        $process = new Process(['pkill', '-USR1', 'srt-daemon']);
        $process->setTimeout(5);
        $process->run();
    }

    private function formatStream(SrtStream $stream): array
    {
        $serverHost = config('app.url')
            ? parse_url(config('app.url'), PHP_URL_HOST)
            : 'localhost';

        return [
            'id'         => $stream->id,
            'name'       => $stream->name,
            'stream_id'  => $stream->stream_id,
            'srt_port'   => $stream->srt_port,
            'rtmp_stream'=> $stream->rtmp_stream,
            'description'=> $stream->description,
            'enabled'    => $stream->enabled,
            'bitrate'    => $stream->bitrate,
            'resolution' => $stream->resolution,
            'codec_video'=> $stream->codec_video,
            'codec_audio'=> $stream->codec_audio,
            'status'     => $stream->status,
            'last_connected_at' => $stream->last_connected_at,
            'error_log'  => $stream->error_log,
            'srt_url'    => "srt://{$serverHost}:{$stream->srt_port}?streamid={$stream->stream_id}",
            'rtmp_url'   => "rtmp://127.0.0.1:1935/{$stream->rtmp_stream}",
            'hls_url'    => "http://{$serverHost}/{$stream->rtmp_stream}/index.m3u8",
            'dash_url'   => "http://{$serverHost}/{$stream->rtmp_stream}/manifest.mpd",
        ];
    }
}
