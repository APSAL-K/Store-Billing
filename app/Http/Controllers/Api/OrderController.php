<?php

namespace App\Http\Controllers\Api;

use App\Data\NewOrderData;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Customer;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orders->place(NewOrderData::fromValidated($request->validated()));

        return OrderResource::make($order)
            ->response()
            ->setStatusCode(201);
    }

    public function update(StoreOrderRequest $request, Order $order): OrderResource
    {
        return OrderResource::make(
            $this->orders->update($order, NewOrderData::fromValidated($request->validated()))
        );
    }

    public function destroy(Order $order): JsonResponse
    {
        $this->orders->delete($order);

        return response()->json(null, 204);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'include_deleted' => ['nullable', 'boolean'],
        ]);

        $customer = Customer::where('email', strtolower(trim($validated['email'])))->firstOrFail();

        $orders = Order::where('customer_id', $customer->id)
            ->when($request->boolean('include_deleted'), fn ($query) => $query->withTrashed())
            ->with(['customer', 'items.product'])
            ->latest('placed_at')
            ->latest('id')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return OrderResource::collection($orders);
    }
}
