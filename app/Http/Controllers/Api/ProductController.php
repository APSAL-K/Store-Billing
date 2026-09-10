<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RestockProductRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
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

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return ProductResource::make($product)->response()->setStatusCode(201);
    }

    public function update(StoreProductRequest $request, Product $product): ProductResource
    {
        $product->update($request->validated());

        return ProductResource::make($product);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(null, 204);
    }

    public function restock(
        RestockProductRequest $request,
        Product $product,
        InventoryService $inventory,
    ): ProductResource {
        return ProductResource::make($inventory->restock(
            $product,
            $request->integer('quantity'),
            $request->input('note'),
        ));
    }

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
