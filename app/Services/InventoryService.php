<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function restock(Product $product, int $quantity, ?string $note = null): Product
    {
        return DB::transaction(function () use ($product, $quantity, $note): Product {
            $locked = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $balanceAfter = $locked->stock_on_hand + $quantity;

            Product::whereKey($locked->id)->increment('stock_on_hand', $quantity);

            StockMovement::create([
                'product_id' => $locked->id,
                'reason' => StockMovement::REASON_RESTOCK,
                'note' => $note,
                'quantity_change' => $quantity,
                'balance_after' => $balanceAfter,
            ]);

            return $locked->refresh();
        });
    }
}
