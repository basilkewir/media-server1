<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutputTarget extends Model
{
    use HasFactory;

    protected $table = 'output_targets';

    protected $fillable = [
        'channel_id', 'name', 'output_url', 'output_protocol', 'trigger',
        // Transcoding — all nullable, null/copy = passthrough (zero latency)
        'video_codec', 'audio_codec',
        'video_bitrate_kbps', 'audio_bitrate_kbps',
        'resolution', 'framerate',
        // SRT options
        'srt_passphrase', 'srt_latency_ms',
        // State
        'is_enabled', 'status', 'pid', 'reconnect_attempts',
        'connected_at', 'last_error_at', 'last_error', 'bytes_sent',
        'metadata',
    ];

    protected $casts = [
        'is_enabled'    => 'boolean',
        'connected_at'  => 'datetime',
        'last_error_at' => 'datetime',
        'metadata'      => 'json',
    ];

    // Trigger constants
    const TRIGGER_ALWAYS        = 'always';
    const TRIGGER_LIVE_ONLY     = 'live_only';
    const TRIGGER_FALLBACK_ONLY = 'fallback_only';
    const TRIGGER_MANUAL        = 'manual';
    const TRIGGER_FALLBACK_AUDIO = 'fallback_audio';

    // Status constants
    const STATUS_IDLE         = 'idle';
    const STATUS_CONNECTING   = 'connecting';
    const STATUS_CONNECTED    = 'connected';
    const STATUS_RECONNECTING = 'reconnecting';
    const STATUS_ERROR        = 'error';
    const STATUS_STOPPED      = 'stopped';

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(OutputTargetLog::class);
    }

    public function isRunning(): bool
    {
        return $this->pid && file_exists("/proc/{$this->pid}");
    }

    public function getDurationSeconds(): int
    {
        if (!$this->connected_at) return 0;
        return (int) now()->diffInSeconds($this->connected_at);
    }

    /**
     * True when any codec conversion or scaling is configured.
     * False = pure passthrough, zero added latency.
     */
    public function isPassthrough(): bool
    {
        if (in_array($this->output_protocol, ['icecast', 'shoutcast'])) {
            return false;
        }

        return (!$this->video_codec || $this->video_codec === 'copy')
            && (!$this->audio_codec || $this->audio_codec === 'copy')
            && !$this->resolution
            && !$this->framerate;
    }

    public function getSourceType(): string
    {
        return $this->metadata['source_type'] ?? 'unknown';
    }

    public function toStatusArray(): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'output_protocol'    => $this->output_protocol,
            'output_url'         => $this->output_url,
            'trigger'            => $this->trigger,
            'is_enabled'         => $this->is_enabled,
            'is_passthrough'     => $this->isPassthrough(),
            'source_type'        => $this->getSourceType(),
            'status'             => $this->status,
            'process_alive'      => $this->isRunning(),
            'connected_at'       => $this->connected_at,
            'duration_seconds'   => $this->getDurationSeconds(),
            'reconnect_attempts' => $this->reconnect_attempts,
            'last_error'         => $this->last_error,
            // Transcoding profile (null = copy/passthrough)
            'video_codec'        => $this->video_codec ?: 'copy',
            'audio_codec'        => $this->audio_codec ?: 'copy',
            'video_bitrate_kbps' => $this->video_bitrate_kbps,
            'audio_bitrate_kbps' => $this->audio_bitrate_kbps,
            'resolution'         => $this->resolution,
            'framerate'          => $this->framerate,
            'srt_latency_ms'     => $this->srt_latency_ms,
        ];
    }
}
