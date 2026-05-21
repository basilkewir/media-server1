<?php

declare(strict_types=1);

namespace App\Http\Requests\Stream;

use Illuminate\Foundation\Http\FormRequest;

class ProbeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => 'required|string|stream_url|max:2048',
        ];
    }
}
