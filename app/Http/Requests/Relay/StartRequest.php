<?php

declare(strict_types=1);

namespace App\Http\Requests\Relay;

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
            'relay_server_id' => 'required|integer|exists:relay_servers,id',
        ];
    }
}
