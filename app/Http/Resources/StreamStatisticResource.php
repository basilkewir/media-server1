<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StreamStatisticResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stream_id' => $this->stream_id,
            'viewers' => $this->viewers,
            'bitrate_kbps' => $this->bitrate_kbps,
            'framerate' => $this->framerate,
            'is_healthy' => $this->is_healthy,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
