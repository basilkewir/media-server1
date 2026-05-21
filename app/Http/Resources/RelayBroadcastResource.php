<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RelayBroadcastResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'channel_id' => $this->channel_id,
            'relay_server_id' => $this->relay_server_id,
            'status' => $this->status,
            'relay_url' => $this->relay_url,
            'is_active' => $this->is_active,
            'bitrate_kbps' => $this->bitrate_kbps,
            'listeners' => $this->listeners,
            'process_alive' => $this->metadata['relay_process_pid'] ?? null
                ? file_exists('/proc/' . $this->metadata['relay_process_pid'])
                : false,
            'duration_seconds' => $this->created_at ? now()->diffInSeconds($this->created_at) : 0,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
