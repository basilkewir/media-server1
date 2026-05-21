<?php

declare(strict_types=1);

namespace App\Http\Requests\Channel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $channelId = $this->route('channel')?->id;

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('channels')->ignore($channelId)],
            'slug' => ['sometimes', 'string', 'max:255', 'slug_format', Rule::unique('channels')->ignore($channelId)],
            'description' => 'nullable|string|max:1000',
            'vod_playlist_url' => 'nullable|string|stream_url|max:2048',
            'rtmp_push_url' => 'nullable|string|stream_url|max:2048',
            'bitrate_kbps' => 'nullable|integer|min:32|max:50000',
            'resolution' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
            'is_icecast_enabled' => 'nullable|boolean',
            'is_relay_enabled' => 'nullable|boolean',
        ];
    }
}
