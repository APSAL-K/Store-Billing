<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->value();
        $onlyLowStock = $request->boolean('low_stock');

        $products = Product::query()
            ->when($search !== '', fn ($query) => $query->where(
                fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%')
            ))
            ->when($onlyLowStock, fn ($query) => $query->lowOnStock())
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $all = Product::all();

        return view('products.index', [
            'products' => $products,
            'search' => $search,
            'onlyLowStock' => $onlyLowStock,
            'stats' => [
                'total' => $all->count(),
                'low' => $all->filter(
                    fn (Product $product): bool => $product->stock_on_hand <= $product->effectiveLowStockThreshold()
                )->count(),
                'out' => $all->where('stock_on_hand', 0)->count(),
                'units' => $all->sum('stock_on_hand'),
            ],
        ]);
    }
}
