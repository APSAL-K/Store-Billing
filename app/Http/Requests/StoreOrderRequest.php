<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer' => ['required', 'array'],
            'customer.email' => ['required', 'email:rfc', 'max:255'],
            'customer.name' => [
                Rule::requiredIf(fn (): bool => ! $this->customerIsOnFile()),
                'nullable',
                'string',
                'max:255',
            ],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10000'],

            'amount_tendered' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer.name.required' => 'A name is required the first time we see this email address.',
            'items.required' => 'An order needs at least one product line.',
            'items.*.product_id.exists' => 'One of the selected products no longer exists.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('customer.email'))) {
            $this->merge([
                'customer' => array_merge($this->input('customer'), [
                    'email' => strtolower(trim($this->input('customer.email'))),
                ]),
            ]);
        }
    }

    private function customerIsOnFile(): bool
    {
        $email = $this->input('customer.email');

        return is_string($email) && Customer::where('email', strtolower(trim($email)))->exists();
    }
}
