<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VoidOrderRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
