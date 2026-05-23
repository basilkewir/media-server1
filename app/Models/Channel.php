<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Channel extends Model
{
    use HasFactory;

    protected $table = 'channels';

    protected $fillable = [
        'name', 'slug', 'description',
        'vod_playlist_url',
        'push_url',
        'rtmp_push_url',
        'is_active', 'is_live', 'is_icecast_enabled', 'is_relay_enabled',
        'audio_relay_enabled', 'audio_relay_playlist_url', 'audio_source_url',
        'audio_fallback_enabled', 'audio_relay_target_url', 'audio_relay_protocol',
        'bitrate_kbps', 'resolution', 'metadata',
        'logo_path', 'logo_position', 'logo_opacity', 'logo_width', 'logo_height',
        'watermark_path', 'watermark_position', 'watermark_opacity',
        'ticker_text', 'ticker_position', 'ticker_text_color', 'ticker_bg_color',
        'ticker_font_size', 'ticker_speed_ms', 'ticker_enabled',
    ];

    protected $casts = [
        'is_active'          => 'boolean',
        'is_live'            => 'boolean',
        'is_icecast_enabled' => 'boolean',
        'is_relay_enabled'   => 'boolean',
        'audio_relay_enabled'=> 'boolean',
        'audio_fallback_enabled' => 'boolean',
        'metadata'           => 'json',
        'logo_opacity'       => 'integer',
        'logo_width'         => 'integer',
        'logo_height'        => 'integer',
        'watermark_opacity'  => 'integer',
        'ticker_font_size'   => 'integer',
        'ticker_speed_ms'    => 'integer',
        'ticker_enabled'     => 'boolean',
    ];

    public function streams(): HasMany
    {
        return $this->hasMany(Stream::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(StreamEvent::class);
    }

    public function relays(): HasMany
    {
        return $this->hasMany(RelayBroadcast::class);
    }

    public function outputTargets(): HasMany
    {
        return $this->hasMany(OutputTarget::class);
    }

    public function srtStreams(): HasMany
    {
        return $this->hasMany(\App\Models\SrtStream::class);
    }

    public function vodFiles(): HasMany
    {
        return $this->hasMany(\App\Models\VodFile::class);
    }

    public function vodSchedules(): HasMany
    {
        return $this->hasMany(\App\Models\VodSchedule::class);
    }

    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'channel_user');
    }

    public function activeStream(): ?Stream
    {
        return $this->streams()
            ->whereIn('status', ['active', 'fallback'])
            ->latest()
            ->first();
    }

    public function getStatus(): array
    {
        $stream = $this->activeStream();

        return [
            'channel_id'       => $this->id,
            'name'             => $this->name,
            'slug'             => $this->slug,
            'is_live'          => $this->is_live,
            'is_active'        => $this->is_active,
            'is_icecast'       => $this->is_icecast_enabled,
            'is_relay'         => $this->is_relay_enabled,
            'stream_type'      => $stream?->stream_type,
            'stream_status'    => $stream?->status,
            'input_protocol'   => $stream?->input_protocol,
            'vod_playlist_url' => $this->vod_playlist_url,
            'rtmp_push_url'    => $this->rtmp_push_url,
            'hls_url'          => url("/streams/{$this->slug}/playlist.m3u8"),
            'dash_url'         => url("/streams/{$this->slug}/manifest.mpd"),
            'player_url'       => url("/play/{$this->slug}"),
        ];
    }
}
