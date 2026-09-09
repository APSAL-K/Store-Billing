<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('search')->trim()->value();

        $customers = Customer::query()
            ->when($search !== '', fn ($query) => $query->where(
                fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')
            ))
            ->withCount('orders')
            ->orderBy('name')
            ->get();

        return CustomerResource::collection($customers);
    }

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

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = Customer::create($request->validated());

        return CustomerResource::make($customer->loadCount('orders'))
            ->response()
            ->setStatusCode(201);
    }

    public function update(StoreCustomerRequest $request, Customer $customer): CustomerResource
    {
        $customer->update($request->validated());

        return CustomerResource::make($customer->loadCount('orders'));
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();

        return response()->json(null, 204);
    }
}
