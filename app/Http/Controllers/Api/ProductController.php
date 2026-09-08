<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $products = Product::query()
            ->when($request->filled('search'), fn ($query) => $query->where(
                fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%')
                    ->orWhere('code', 'like', '%'.$request->string('search').'%')
            ))
            ->orderBy('name')
            ->get();

        return ProductResource::collection($products);
    }

    /**
     * Products at or below the low-stock threshold.
     *
     * The threshold resolves in this order: an explicit "threshold" parameter,
     * then the product's own low_stock_threshold, then the application default
     * in config/inventory.php.
     */
    public function lowStock(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'threshold' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        $products = Product::query()
            ->lowOnStock(isset($validated['threshold']) ? (int) $validated['threshold'] : null)
            ->orderBy('stock_on_hand')
            ->orderBy('name')
            ->get();

        return ProductResource::collection($products);
    }
}
