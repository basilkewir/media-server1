<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SrtStream extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'stream_id',
        'srt_port',
        'rtmp_stream',
        'description',
        'enabled',
        'bitrate',
        'resolution',
        'codec_video',
        'codec_audio',
        'status',
        'last_connected_at',
        'error_log',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'last_connected_at' => 'datetime',
    ];

    /**
     * Get all active SRT streams
     */
    public static function getActive()
    {
        return self::where('enabled', true)
            ->where('deleted_at', null)
            ->get();
    }

    /**
     * Get next available SRT port
     */
    public static function getNextAvailablePort()
    {
        $basePort = 9000;
        $maxStreams = 100;

        $existingPorts = self::pluck('srt_port')->toArray();

        for ($i = 0; $i < $maxStreams; $i++) {
            $port = $basePort + $i;
            if (!in_array($port, $existingPorts)) {
                return $port;
            }
        }

        return null;
    }

    /**
     * Get next available UDP relay port
     */
    public static function getNextUdpPort()
    {
        $basePort = 5000;
        $maxStreams = 100;
        $existingUdpPorts = [];

        foreach (self::all() as $stream) {
            $existingUdpPorts[] = $basePort + ($stream->srt_port - 9000);
        }

        for ($i = 0; $i < $maxStreams; $i++) {
            $port = $basePort + $i;
            if (!in_array($port, $existingUdpPorts)) {
                return $port;
            }
        }

        return null;
    }

    /**
     * Generate unique stream ID
     */
    public static function generateStreamId($name)
    {
        $streamId = strtolower(preg_replace('/[^a-z0-9]/i', '', $name));
        $original = $streamId;
        $counter = 1;

        while (self::where('stream_id', $streamId)->exists()) {
            $streamId = $original . $counter;
            $counter++;
        }

        return $streamId;
    }

    /**
     * Update stream status
     */
    public function updateStatus($status, $message = null)
    {
        $this->status = $status;
        if ($message) {
            $this->error_log = $message;
        }
        $this->save();
    }

    /**
     * Mark as connected
     */
    public function markConnected()
    {
        $this->status = 'connected';
        $this->last_connected_at = now();
        $this->error_log = null;
        $this->save();
    }

    /**
     * Get connection status
     */
    public function isConnected()
    {
        return $this->status === 'connected';
    }

    /**
     * Get stream configuration for srt-server
     */
    public function toSrtConfig()
    {
        $udpPort = 5000 + ($this->srt_port - 9000);

        return [
            'name' => $this->name,
            'stream_id' => $this->stream_id,
            'srt_port' => $this->srt_port,
            'streamid' => $this->stream_id,
            'udp_port' => $udpPort,
            'rtmp_stream' => $this->rtmp_stream,
        ];
    }
}
