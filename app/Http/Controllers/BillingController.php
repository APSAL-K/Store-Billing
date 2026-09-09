<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;

class BillingController extends Controller
{
    /**
     * The counter screen. The catalogue is handed to the page up front so the
     * cashier can search and pick lines without a round trip per keystroke.
     */
    public function __invoke(): View
    {
        $products = Product::orderBy('name')->get();

        return view('billing.index', [
            'lowStock' => $products->filter(
                fn (Product $product): bool => $product->stock_on_hand <= $product->effectiveLowStockThreshold()
            )->sortBy('stock_on_hand')->values(),
            'catalogue' => $products->map(fn (Product $product): array => [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'unit_price' => (float) $product->unit_price,
                'tax_percentage' => (float) $product->tax_percentage,
                'stock_on_hand' => $product->stock_on_hand,
            ])->values(),
        ]);
    }
}
