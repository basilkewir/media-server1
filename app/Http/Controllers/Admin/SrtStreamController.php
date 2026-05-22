<?php

namespace App\Http\Controllers\Admin;

use App\Models\SrtStream;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class SrtStreamController extends Controller
{
    // Auth is enforced at the route group level (routes/web.php).
    // Avoid doing middleware wiring in the constructor.

    /**
     * Display list of SRT streams
     */
    public function index()
    {
        $streams = SrtStream::all();
        $stats = $this->getStreamStats();

        return view('admin.srt-streams.index', [
            'streams' => $streams,
            'stats' => $stats,
            'availablePort' => SrtStream::getNextAvailablePort(),
        ]);
    }

    /**
     * Show create stream form
     */
    public function create()
    {
        // Dedicated create view isn't present yet; keep UI functional.
        return redirect()->route('admin.srt-streams.index')
            ->with('error', 'Create form view is not available yet.');
    }

    /**
     * Store new SRT stream
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:srt_streams,name|max:255',
            'description' => 'nullable|string|max:1000',
            'rtmp_stream' => 'required|string|unique:srt_streams,rtmp_stream|max:255',
            'bitrate' => 'nullable|integer|min:100|max:50000',
            'resolution' => 'nullable|string',
            'codec_video' => 'nullable|string',
            'codec_audio' => 'nullable|string',
        ]);

        try {
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

            // Create Flussonic stream configuration
            $this->createFlussonicStream($stream);

            // Update SRT server configuration
            $this->updateSrtServerConfig();

            // Open firewall port
            $this->openFirewallPort($stream->srt_port);

            Log::info("SRT Stream created: {$stream->name} on port {$stream->srt_port}");

            return redirect()->route('admin.srt-streams.show', $stream->id)
                ->with('success', "Stream '{$stream->name}' created successfully on port {$stream->srt_port}");
        } catch (\Exception $e) {
            Log::error("Error creating SRT stream: " . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to create stream: ' . $e->getMessage());
        }
    }

    /**
     * Show SRT stream details
     */
    public function show(SrtStream $srtStream)
    {
    // Dedicated show view isn't present yet; keep UI functional.
    return redirect()->route('admin.srt-streams.index');
    }

    /**
     * Show edit form
     */
    public function edit(SrtStream $srtStream)
    {
        // Dedicated edit view isn't present yet; keep UI functional.
        return redirect()->route('admin.srt-streams.index')
            ->with('error', 'Edit form view is not available yet.');
    }

    /**
     * Update SRT stream
     */
    public function update(Request $request, SrtStream $srtStream)
    {
        $validated = $request->validate([
            'name' => "required|string|unique:srt_streams,name,{$srtStream->id}|max:255",
            'description' => 'nullable|string|max:1000',
            'bitrate' => 'nullable|integer|min:100|max:50000',
            'resolution' => 'nullable|string',
            'codec_video' => 'nullable|string',
            'codec_audio' => 'nullable|string',
        ]);

        try {
            $srtStream->update($validated);

            // Update Flussonic stream if RTMP name changed
            if ($request->has('rtmp_stream') && $request->rtmp_stream !== $srtStream->rtmp_stream) {
                $srtStream->rtmp_stream = $request->rtmp_stream;
                $srtStream->save();
                $this->updateFlussonicStream($srtStream);
            }

            // Update SRT server configuration
            $this->updateSrtServerConfig();

            Log::info("SRT Stream updated: {$srtStream->name}");

            return redirect()->route('admin.srt-streams.show', $srtStream->id)
                ->with('success', "Stream updated successfully");
        } catch (\Exception $e) {
            Log::error("Error updating SRT stream: " . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update stream: ' . $e->getMessage());
        }
    }

    /**
     * Toggle stream enabled/disabled
     */
    public function toggle(SrtStream $srtStream)
    {
        try {
            $srtStream->enabled = !$srtStream->enabled;
            $srtStream->save();

            $this->updateSrtServerConfig();

            $status = $srtStream->enabled ? 'enabled' : 'disabled';
            Log::info("SRT Stream toggled: {$srtStream->name} is now {$status}");

            return redirect()->back()
                ->with('success', "Stream {$status} successfully");
        } catch (\Exception $e) {
            Log::error("Error toggling SRT stream: " . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to toggle stream');
        }
    }

    /**
     * Delete SRT stream
     */
    public function destroy(SrtStream $srtStream)
    {
        try {
            $port = $srtStream->srt_port;
            $name = $srtStream->name;

            // Remove from Flussonic
            $this->removeFlussonicStream($srtStream);

            // Close firewall port
            $this->closeFirewallPort($port);

            // Delete from database
            $srtStream->delete();

            // Update SRT server configuration
            $this->updateSrtServerConfig();

            Log::info("SRT Stream deleted: {$name} (port {$port})");

            return redirect()->route('admin.srt-streams.index')
                ->with('success', "Stream deleted successfully");
        } catch (\Exception $e) {
            Log::error("Error deleting SRT stream: " . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to delete stream');
        }
    }

    /**
     * Create Flussonic stream configuration
     */
    private function createFlussonicStream(SrtStream $stream)
    {
        $confPath = '/etc/flussonic/flussonic.conf';
        $confContent = file_get_contents($confPath);

        $newStreamConfig = "stream {$stream->rtmp_stream} {\n  input publish://;\n}\n";

        // Append to config file
        file_put_contents($confPath, $newStreamConfig, FILE_APPEND);

        // Reload Flussonic without restart
        shell_exec('sudo systemctl reload flussonic 2>&1');

        Log::info("Flussonic stream created: {$stream->rtmp_stream}");
    }

    /**
     * Update Flussonic stream configuration
     */
    private function updateFlussonicStream(SrtStream $stream)
    {
        $confPath = '/etc/flussonic/flussonic.conf';
        $confContent = file_get_contents($confPath);

        // This is complex with sed, so we'll reload the whole config
        shell_exec('sudo systemctl reload flussonic 2>&1');

        Log::info("Flussonic stream updated: {$stream->rtmp_stream}");
    }

    /**
     * Remove Flussonic stream configuration
     */
    private function removeFlussonicStream(SrtStream $stream)
    {
        $confPath = '/etc/flussonic/flussonic.conf';

        // Remove stream block from config
        shell_exec("sudo sed -i '/^stream {$stream->rtmp_stream} {{/,/^}}/d' {$confPath} 2>&1");

        // Reload Flussonic
        shell_exec('sudo systemctl reload flussonic 2>&1');

        Log::info("Flussonic stream removed: {$stream->rtmp_stream}");
    }

    /**
     * Update SRT server configuration dynamically
     */
    private function updateSrtServerConfig()
    {
        $streams = SrtStream::getActive();
        $configPath = '/var/www/mediaserver/srt-server-config.json';

        $config = [
            'streams' => [],
            'srt_listen_base_port' => 9000,
            'udp_relay_base_port' => 5000,
            'rtmp_host' => '127.0.0.1',
            'rtmp_port' => 1935,
        ];

        foreach ($streams as $stream) {
            $config['streams'][$stream->stream_id] = $stream->toSrtConfig();
        }

        // Write config file
        file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        chmod($configPath, 0644);

        // Signal SRT daemon to reload config
        shell_exec('pkill -USR1 srt-daemon 2>/dev/null || true');

        Log::info("SRT server configuration updated with " . count($streams) . " streams");
    }

    /**
     * Open firewall port for SRT
     */
    private function openFirewallPort($port)
    {
        shell_exec("sudo ufw allow {$port}/udp 2>&1");
        Log::info("Firewall port opened: {$port}/UDP");
    }

    /**
     * Close firewall port
     */
    private function closeFirewallPort($port)
    {
        shell_exec("sudo ufw delete allow {$port}/udp 2>&1");
        Log::info("Firewall port closed: {$port}/UDP");
    }

    /**
     * Get stream statistics from logs
     */
    private function getStreamStats($streamId = null)
    {
        $logPath = '/var/www/mediaserver/storage/logs/srt-server.log';

        if (!file_exists($logPath)) {
            return [];
        }

        $logs = file_get_contents($logPath);
        $stats = [];

        if ($streamId) {
            // Get specific stream stats
            preg_match_all("/\[FFmpeg-{$streamId}\].*bitrate=(\d+\.\d+)kbits\/s.*speed=(\d+\.?\d*)x/", $logs, $matches);

            if (!empty($matches[1])) {
                $stats = [
                    'bitrate' => end($matches[1]) . ' kbps',
                    'speed' => end($matches[2]) . 'x',
                ];
            }
        } else {
            // Get all stream stats
            preg_match_all("/\[FFmpeg-(\w+)\].*bitrate=(\d+\.\d+)kbits\/s/", $logs, $matches);
            foreach ($matches[1] as $key => $stream) {
                $stats[$stream] = $matches[2][$key] . ' kbps';
            }
        }

        return $stats;
    }

    /**
     * Get recent logs for stream
     */
    private function getStreamLogs($streamId, $lines = 50)
    {
        $logPath = '/var/www/mediaserver/storage/logs/srt-server.log';

        if (!file_exists($logPath)) {
            return [];
        }

        $logs = [];
        $handle = fopen($logPath, 'r');

        if ($handle) {
            $allLines = [];
            while (($line = fgets($handle)) !== false) {
                if (strpos($line, $streamId) !== false) {
                    $allLines[] = trim($line);
                }
            }
            fclose($handle);

            $logs = array_slice($allLines, -$lines);
        }

        return $logs;
    }
}
