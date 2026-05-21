<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OutputTargetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'channel_id' => $this->channel_id,
            'name' => $this->name,
            'output_protocol' => $this->output_protocol,
            'output_url' => $this->output_url,
            'trigger' => $this->trigger,
            'is_enabled' => $this->is_enabled,
            'is_passthrough' => $this->isPassthrough(),
            'source_type' => $this->getSourceType(),
            'status' => $this->status,
            'process_alive' => $this->isRunning(),
            'connected_at' => $this->connected_at?->toIso8601String(),
            'duration_seconds' => $this->getDurationSeconds(),
            'reconnect_attempts' => $this->reconnect_attempts,
            'last_error' => $this->last_error,
            'last_error_at' => $this->last_error_at?->toIso8601String(),
            'video_codec' => $this->video_codec ?: 'copy',
            'audio_codec' => $this->audio_codec ?: 'copy',
            'video_bitrate_kbps' => $this->video_bitrate_kbps,
            'audio_bitrate_kbps' => $this->audio_bitrate_kbps,
            'resolution' => $this->resolution,
            'framerate' => $this->framerate,
            'srt_latency_ms' => $this->srt_latency_ms,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
