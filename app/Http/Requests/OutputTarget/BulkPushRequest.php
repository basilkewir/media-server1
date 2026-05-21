<?php

declare(strict_types=1);

namespace App\Http\Requests\OutputTarget;

use Illuminate\Foundation\Http\FormRequest;

class BulkPushRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channel_ids' => 'required|array|min:1',
            'channel_ids.*' => 'integer|exists:channels,id',
            'target_ids' => 'required|array|min:1',
            'target_ids.*' => 'integer|exists:output_targets,id',
        ];
    }
}
