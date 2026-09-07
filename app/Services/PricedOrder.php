<?php

namespace App\Services;

final readonly class PricedOrder
{
    /**
     * @param  array<int, array<string, mixed>>  $items  Attributes ready for OrderItem::createMany().
     */
    public function __construct(
        public array $items,
        public int $subtotalMinor,
        public int $taxTotalMinor,
        public int $grandTotalMinor,
    ) {}
}
