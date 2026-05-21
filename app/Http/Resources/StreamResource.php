<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StreamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'channel_id' => $this->channel_id,
            'status' => $this->status,
            'stream_type' => $this->stream_type,
            'source_url' => $this->source_url,
            'input_protocol' => $this->input_protocol,
            'started_at' => $this->started_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'duration_seconds' => $this->getDuration(),
            'uptime_percentage' => $this->getUptimePercentage(),
            'bitrate_kbps' => $this->bitrate_kbps,
            'resolution' => $this->resolution,
            'viewers' => $this->viewers,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
