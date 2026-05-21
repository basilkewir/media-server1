<?php

declare(strict_types=1);

namespace App\Http\Requests\Channel;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:channels',
            'slug' => 'required|string|max:255|unique:channels|slug_format',
            'description' => 'nullable|string|max:1000',
            'vod_playlist_url' => 'nullable|string|stream_url|max:2048',
            'rtmp_push_url' => 'nullable|string|stream_url|max:2048',
            'bitrate_kbps' => 'nullable|integer|min:32|max:50000',
            'resolution' => 'nullable|string|max:50',
            'is_icecast_enabled' => 'nullable|boolean',
            'is_relay_enabled' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'slug.slug_format' => 'The slug must contain only lowercase letters, numbers, and hyphens.',
            'vod_playlist_url.stream_url' => 'The VOD playlist URL must be a valid streaming URL.',
            'rtmp_push_url.stream_url' => 'The RTMP push URL must be a valid streaming URL.',
        ];
    }
}
