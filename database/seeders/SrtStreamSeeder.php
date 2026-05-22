<?php

namespace Database\Seeders;

use App\Models\SrtStream;
use Illuminate\Database\Seeder;

class SrtStreamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Existing channels that are already receiving SRT streams
        $streams = [
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

        foreach ($streams as $stream) {
            // Check if stream already exists (by stream_id or srt_port)
            $exists = SrtStream::where('stream_id', $stream['stream_id'])
                ->orWhere('srt_port', $stream['srt_port'])
                ->exists();

            if (!$exists) {
                SrtStream::create($stream);
                echo "✓ Created stream: {$stream['name']}\n";
            } else {
                echo "→ Stream already exists: {$stream['name']}\n";
            }
        }
    }
}
