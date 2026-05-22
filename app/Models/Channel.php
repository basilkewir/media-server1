<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'bitrate_kbps', 'resolution', 'metadata',
    ];

    protected $casts = [
        'is_active'          => 'boolean',
        'is_live'            => 'boolean',
        'is_icecast_enabled' => 'boolean',
        'is_relay_enabled'   => 'boolean',
        'metadata'           => 'json',
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
