<?php

namespace App\Console\Commands;

use App\Models\SrtStream;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SrtCreateStream extends Command
{
    protected $signature = 'srt:create-stream {stream_id : The SRT stream ID}';
    protected $description = 'Create a new SRT stream and configure Flussonic';

    public function handle()
    {
        $stream = SrtStream::find($this->argument('stream_id'));

        if (!$stream) {
            $this->error('Stream not found');
            return 1;
        }

        try {
            // Create Flussonic stream configuration
            $confPath = '/etc/flussonic/flussonic.conf';
            $newStreamConfig = "stream {$stream->rtmp_stream} {\n  input publish://;\n}\n";

            file_put_contents($confPath, $newStreamConfig, FILE_APPEND);
            shell_exec('sudo systemctl reload flussonic 2>&1');

            // Open firewall port
            shell_exec("sudo ufw allow {$stream->srt_port}/udp 2>&1");

            // Update SRT daemon configuration
            $this->updateSrtConfig();

            // Signal daemon to reload
            shell_exec("pkill -USR1 srt-daemon 2>/dev/null || true");

            Log::info("SRT Stream created: {$stream->name} on port {$stream->srt_port}");
            $this->info("Stream '{$stream->name}' created successfully");
            $this->info("SRT Port: {$stream->srt_port}");
            $this->info("RTMP Stream: {$stream->rtmp_stream}");

            return 0;
        } catch (\Exception $e) {
            Log::error("Error creating SRT stream: " . $e->getMessage());
            $this->error("Failed to create stream: " . $e->getMessage());
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
