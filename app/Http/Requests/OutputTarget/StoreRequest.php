<?php

declare(strict_types=1);

namespace App\Http\Requests\OutputTarget;

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
            'name' => 'required|string|max:255',
            'output_url' => 'required|string|stream_url|max:2048',
            'output_protocol' => 'required|in:rtmp,rtmps,srt,hls_push,mpeg_ts_udp,mpeg_ts_tcp,rtp,icecast,shoutcast,file',
            'trigger' => 'in:always,live_only,fallback_only,manual',
            'video_codec' => 'nullable|in:copy,libx264,libx265,libvpx-vp9',
            'audio_codec' => 'nullable|in:copy,aac,libmp3lame,libopus',
            'video_bitrate_kbps' => 'nullable|integer|min:100|max:50000',
            'audio_bitrate_kbps' => 'nullable|integer|min:32|max:512',
            'resolution' => ['nullable', 'string', 'regex:/^\d+x\d+$/'],
            'framerate' => 'nullable|integer|min:1|max:120',
            'srt_passphrase' => 'nullable|string|min:10|max:79',
            'srt_latency_ms' => 'nullable|integer|min:20|max:8000',
            'is_enabled' => 'boolean',
        ];
    }
}
