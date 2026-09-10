<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:64',
                'regex:/^[A-Za-z0-9][A-Za-z0-9\-_]*$/',
                Rule::unique('products', 'code')->ignore($this->route('product')),
            ],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'tax_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'stock_on_hand' => [$this->isMethod('POST') ? 'required' : 'prohibited', 'integer', 'min:0', 'max:1000000'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.unique' => 'Another product already uses that code.',
            'code.regex' => 'Use letters, digits, dashes or underscores only.',
            'stock_on_hand.prohibited' => 'Stock is moved by restocking or by a bill, never edited directly.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge(['code' => strtoupper(trim($this->input('code')))]);
        }

        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }
}
