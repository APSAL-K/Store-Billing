<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function show(Request $request): CustomerResource
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
        ]);

        $customer = Customer::withCount('orders')
            ->where('email', strtolower(trim($validated['email'])))
            ->firstOrFail();

        return CustomerResource::make($customer);
    }
}
