<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    /**
     * @param  array<int, array{product_id: int, code: string, name: string, requested: int, available: int}>  $shortages
     */
    public function __construct(public readonly array $shortages)
    {
        parent::__construct('One or more products do not have enough stock to fulfil this order.');
    }

    public function render(Request $request): ?JsonResponse
    {
        if (! $request->expectsJson()) {
            return null;
        }

        return response()->json([
            'message' => $this->getMessage(),
            'shortages' => $this->shortages,
        ], 422);
    }
}
