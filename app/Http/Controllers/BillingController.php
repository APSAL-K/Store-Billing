<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Contracts\View\View;

class BillingController extends Controller
{
    /**
     * The counter screen. Products are handed to the page up front so the
     * cashier can pick lines without a round trip per keystroke.
     */
    public function index(): View
    {
        $products = Product::orderBy('name')->get();

        return view('billing.index', [
            'products' => $products,
            'lowStock' => Product::lowOnStock()->orderBy('stock_on_hand')->get(),
            'catalogue' => $products->map(fn (Product $product): array => [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'unit_price' => (float) $product->unit_price,
                'tax_percentage' => (float) $product->tax_percentage,
                'stock_on_hand' => $product->stock_on_hand,
            ]),
        ]);
    }

    public function show(Order $order): View
    {
        return view('billing.receipt', [
            'order' => $order->load(['customer', 'items.product']),
        ]);
    }
}
