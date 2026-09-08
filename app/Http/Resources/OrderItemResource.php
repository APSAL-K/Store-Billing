<?php

namespace App\Http\Resources;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderItem
 */
class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'product_id' => $this->product_id,
            'code' => $this->whenLoaded('product', fn () => $this->product->code),
            'name' => $this->whenLoaded('product', fn () => $this->product->name),
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'tax_percentage' => $this->tax_percentage,
            'line_subtotal' => $this->line_subtotal,
            'line_tax' => $this->line_tax,
            'line_total' => $this->line_total,
        ];
    }
}
