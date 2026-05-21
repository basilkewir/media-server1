<?php

declare(strict_types=1);

namespace App\Http\Requests\Icecast;

use Illuminate\Foundation\Http\FormRequest;

class SetMaxListenersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'max_listeners' => 'required|integer|min:1|max:10000',
        ];
    }
}
