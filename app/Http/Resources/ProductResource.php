<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'unit_price' => $this->unit_price,
            'tax_percentage' => $this->tax_percentage,
            'stock_on_hand' => $this->stock_on_hand,
            'low_stock_threshold' => $this->effectiveLowStockThreshold(
                $request->has('threshold') ? (int) $request->integer('threshold') : null
            ),
        ];
    }
}
