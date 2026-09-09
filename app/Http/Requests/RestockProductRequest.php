<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RestockProductRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quantity.min' => 'Restock at least one unit.',
        ];
    }
}
