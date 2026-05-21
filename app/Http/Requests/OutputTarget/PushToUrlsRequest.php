<?php

declare(strict_types=1);

namespace App\Http\Requests\OutputTarget;

use Illuminate\Foundation\Http\FormRequest;

class PushToUrlsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'destinations' => 'required|array|min:1|max:20',
            'destinations.*.url' => 'required|string|stream_url|max:2048',
            'destinations.*.name' => 'nullable|string|max:255',
            'destinations.*.protocol' => 'nullable|in:rtmp,rtmps,srt,mpeg_ts_udp,mpeg_ts_tcp,rtp,hls_push,icecast,shoutcast,file',
            'destinations.*.video_codec' => 'nullable|in:copy,libx264,libx265',
            'destinations.*.audio_codec' => 'nullable|in:copy,aac,libmp3lame',
            'destinations.*.video_bitrate_kbps' => 'nullable|integer|min:100|max:50000',
            'destinations.*.audio_bitrate_kbps' => 'nullable|integer|min:32|max:512',
            'destinations.*.resolution' => 'nullable|string',
            'destinations.*.framerate' => 'nullable|integer|min:1|max:120',
            'destinations.*.srt_latency_ms' => 'nullable|integer|min:20|max:8000',
            'destinations.*.srt_passphrase' => 'nullable|string|max:79',
        ];
    }
}
