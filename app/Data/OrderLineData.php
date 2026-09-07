<?php

namespace App\Data;

final readonly class OrderLineData
{
    public function __construct(
        public int $productId,
        public int $quantity,
    ) {}
}
