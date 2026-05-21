<?php

declare(strict_types=1);

namespace App\Http\Requests\API\AccessCode;

use Illuminate\Foundation\Http\FormRequest;

class ValidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|min:8|max:64',
        ];
    }

    public function messages(): array
    {
        return [
            'code.min' => 'The code field must be at least 8 characters.',
        ];
    }
}
