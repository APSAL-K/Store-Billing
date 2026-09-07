<?php

namespace App\Data;

use Illuminate\Support\Collection;

final readonly class NewOrderData
{
    /**
     * @param  Collection<int, OrderLineData>  $lines
     */
    public function __construct(
        public string $customerEmail,
        public ?string $customerName,
        public Collection $lines,
        public ?float $amountTendered = null,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        $lines = collect($validated['items'])
            ->groupBy('product_id')
            ->map(fn (Collection $rows, int|string $productId) => new OrderLineData(
                productId: (int) $productId,
                quantity: (int) $rows->sum('quantity'),
            ))
            ->values();

        return new self(
            customerEmail: strtolower(trim($validated['customer']['email'])),
            customerName: isset($validated['customer']['name']) ? trim($validated['customer']['name']) : null,
            lines: $lines,
            amountTendered: isset($validated['amount_tendered']) ? (float) $validated['amount_tendered'] : null,
        );
    }
}
