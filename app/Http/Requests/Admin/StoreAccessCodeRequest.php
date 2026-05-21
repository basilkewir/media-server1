<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccessCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:library_only,full_access,premium',
            'duration_days' => 'required|integer|in:30,90,180,365,730',
            'quantity' => 'required|integer|in:5,10,25,50,100',
            'max_uses' => 'nullable|integer|min:1|max:1000',
            'code_length' => 'nullable|integer|min:8|max:32',
            'expires_at' => 'nullable|date|after:today',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Please select a subscription type.',
            'duration_days.required' => 'Please select a subscription duration.',
            'quantity.required' => 'Please select how many codes to generate.',
            'expires_at.after' => 'The expiration date must be in the future.',
            'code_length.min' => 'The code field must be at least 8 characters.',
        ];
    }
}
