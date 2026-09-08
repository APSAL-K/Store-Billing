<?php

namespace App\Http\Resources;

use App\Models\Order;
use App\Support\CashDrawer;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'placed_at' => $this->placed_at?->toIso8601String(),
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'email' => $this->customer->email,
            ]),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'totals' => [
                'subtotal' => $this->subtotal,
                'tax' => $this->tax_total,
                'grand_total' => $this->grand_total,
            ],
            'payment' => $this->when($this->amount_tendered !== null, fn () => [
                'amount_tendered' => $this->amount_tendered,
                'change_due' => $this->change_due,
                'change_breakdown' => CashDrawer::breakdown(Money::toMinor($this->change_due)),
            ]),
        ];
    }
}
