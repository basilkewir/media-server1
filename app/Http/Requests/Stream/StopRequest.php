<?php

declare(strict_types=1);

namespace App\Http\Requests\Stream;

use Illuminate\Foundation\Http\FormRequest;

class StopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channel_id' => 'required|integer|exists:channels,id',
        ];
    }
}
