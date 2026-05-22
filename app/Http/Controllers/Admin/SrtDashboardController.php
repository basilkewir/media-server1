<?php

namespace App\Http\Controllers\Admin;

use App\Models\SrtStream;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SrtDashboardController extends Controller
{
    /**
     * Get SRT streams widget data for dashboard
     */
    public function widget()
    {
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
}
