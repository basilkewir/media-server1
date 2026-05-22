<?php

namespace App\Console\Commands;

use App\Models\SrtStream;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SrtDeleteStream extends Command
{
    protected $signature = 'srt:delete-stream {stream_id : The SRT stream ID}';
    protected $description = 'Delete a SRT stream';

    public function handle()
    {
        $stream = SrtStream::find($this->argument('stream_id'));

        if (!$stream) {
            $this->error('Stream not found');
            return 1;
        }

        try {
            $port = $stream->srt_port;
            $name = $stream->name;

            // Remove from Flussonic
            $confPath = '/etc/flussonic/flussonic.conf';
            shell_exec("sudo sed -i '/^stream {$stream->rtmp_stream} {{/,/^}}/d' {$confPath} 2>&1");
            shell_exec('sudo systemctl reload flussonic 2>&1');

            // Close firewall port
            shell_exec("sudo ufw delete allow {$port}/udp 2>&1");

            // Delete from database
            $stream->delete();

            // Update SRT config
            $this->updateSrtConfig();

            // Signal daemon
            shell_exec("pkill -USR1 srt-daemon 2>/dev/null || true");

            Log::info("SRT Stream deleted: {$name} (port {$port})");
            $this->info("Stream '{$name}' deleted successfully");

            return 0;
        } catch (\Exception $e) {
            Log::error("Error deleting SRT stream: " . $e->getMessage());
            $this->error("Failed to delete stream: " . $e->getMessage());
            return 1;
        }
    }

    private function updateSrtConfig()
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

        file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        chmod($configPath, 0644);
    }
}
