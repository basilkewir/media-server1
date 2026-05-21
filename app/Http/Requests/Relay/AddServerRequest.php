<?php

declare(strict_types=1);

namespace App\Http\Requests\Relay;

use Illuminate\Foundation\Http\FormRequest;

class AddServerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|unique:relay_servers',
            'hostname' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'server_type' => 'required|in:icecast,rtmp,shoutcast',
            'max_listeners' => 'integer|min:1|max:10000',
            'location' => 'nullable|string|max:255',
        ];
    }
}
