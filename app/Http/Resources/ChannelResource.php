<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChannelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'is_live' => $this->is_live,
            'is_icecast_enabled' => $this->is_icecast_enabled,
            'is_relay_enabled' => $this->is_relay_enabled,
            'vod_playlist_url' => $this->vod_playlist_url,
            'rtmp_push_url' => $this->rtmp_push_url,
            'bitrate_kbps' => $this->bitrate_kbps,
            'resolution' => $this->resolution,
            'stream_type' => $this->when($this->relationLoaded('streams'), fn() => $this->activeStream()?->stream_type),
            'stream_status' => $this->when($this->relationLoaded('streams'), fn() => $this->activeStream()?->status),
            'input_protocol' => $this->when($this->relationLoaded('streams'), fn() => $this->activeStream()?->input_protocol),
            'hls_url' => url("/streams/{$this->slug}/playlist.m3u8"),
            'dash_url' => url("/streams/{$this->slug}/manifest.mpd"),
            'player_url' => url("/play/{$this->slug}"),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
