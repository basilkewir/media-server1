<?php

declare(strict_types=1);

namespace App\Http\Requests\Stream;

use Illuminate\Foundation\Http\FormRequest;

class StartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channel_id' => 'required|integer|exists:channels,id',
            'push_url' => 'required|string|stream_url|max:2048',
            'rtmp_push_url' => 'nullable|string|stream_url|max:2048',
        ];
    }
}
