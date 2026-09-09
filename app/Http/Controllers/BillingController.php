<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class BillingController extends Controller
{
    public function create(): View
    {
        $products = Product::orderBy('name')->get();

        return view('billing.index', [
            'order' => null,
            'lowStock' => $this->lowStock($products),
            'catalogue' => $this->catalogue($products),
            'existingLines' => collect(),
        ]);
    }

    public function edit(Order $order): View
    {
        abort_if($order->isVoided(), 404);

        $products = Product::orderBy('name')->get();
        $order->load(['customer', 'items.product']);

        return view('billing.index', [
            'order' => $order,
            'lowStock' => $this->lowStock($products),
            'catalogue' => $this->catalogue($products, $order),
            'existingLines' => $order->items->map(fn ($item): array => [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
            ])->values(),
        ]);
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return Collection<int, Product>
     */
    private function lowStock(Collection $products): Collection
    {
        return $products
            ->filter(fn (Product $p): bool => $p->stock_on_hand <= $p->effectiveLowStockThreshold())
            ->sortBy('stock_on_hand')
            ->values();
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return Collection<int, array<string, mixed>>
     */
    private function catalogue(Collection $products, ?Order $order = null): Collection
    {
        $held = $order?->items->pluck('quantity', 'product_id') ?? collect();

        return $products->map(fn (Product $product): array => [
            'id' => $product->id,
            'code' => $product->code,
            'name' => $product->name,
            'unit_price' => (float) $product->unit_price,
            'tax_percentage' => (float) $product->tax_percentage,
            'stock_on_hand' => $product->stock_on_hand + (int) $held->get($product->id, 0),
        ])->values();
    }
}
