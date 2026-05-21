<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RelayServerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'hostname' => $this->hostname,
            'port' => $this->port,
            'server_type' => $this->server_type,
            'max_listeners' => $this->max_listeners,
            'location' => $this->location,
            'is_active' => $this->is_active,
            'online' => $this->isOnline(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
