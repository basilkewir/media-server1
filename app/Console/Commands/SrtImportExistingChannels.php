<?php

namespace App\Console\Commands;

use App\Models\SrtStream;
use Illuminate\Console\Command;

class SrtImportExistingChannels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'srt:import-existing-channels';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Import existing SRT streams (compassiontv, sudfmtv) into database management';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📡 Importing existing SRT channels...\n');

        $channels = [
            [
                'name' => 'Compassion TV',
                'stream_id' => 'compassiontv',
                'srt_port' => 9000,
                'rtmp_stream' => 'compassiontv',
                'description' => 'Compassion TV Channel - Main broadcast stream',
                'bitrate' => 1500,
                'resolution' => '720p',
                'codec_video' => 'h264',
                'codec_audio' => 'aac',
                'enabled' => true,
                'status' => 'active',
            ],
            [
                'name' => 'SUDFM TV',
                'stream_id' => 'sudfmtv',
                'srt_port' => 9001,
                'rtmp_stream' => 'sudfmtv',
                'description' => 'SUDFM TV Channel - Secondary broadcast stream',
                'bitrate' => 1500,
                'resolution' => '720p',
                'codec_video' => 'h264',
                'codec_audio' => 'aac',
                'enabled' => true,
                'status' => 'active',
            ],
        ];

        $imported = 0;
        $skipped = 0;

        foreach ($channels as $channel) {
            // Check if already exists
            $exists = SrtStream::where('stream_id', $channel['stream_id'])
                ->orWhere('srt_port', $channel['srt_port'])
                ->first();

            if ($exists) {
                $this->warn("  ✗ {$channel['name']} (already exists)");
                $skipped++;
                continue;
            }

            // Create the stream
            try {
                SrtStream::create($channel);
                $this->line("  ✓ {$channel['name']} imported successfully");
                $this->line("    - Port: {$channel['srt_port']}, RTMP: {$channel['rtmp_stream']}\n");
                $imported++;
            } catch (\Exception $e) {
                $this->error("  ✗ Failed to import {$channel['name']}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Summary:");
        $this->line("  Imported: $imported");
        $this->line("  Skipped:  $skipped");
        $this->newLine();

        if ($imported > 0) {
            $this->info("✅ Channels imported successfully!");
            $this->line("You can now manage these channels from the admin panel:");
            $this->line("  http://your-server/admin/srt-streams");
        } else {
            $this->line("All channels were already imported.");
        }

        return 0;
    }
}
