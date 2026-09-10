@props(['product'])

@php
    $stock = $product->stock_on_hand;
    $threshold = $product->effectiveLowStockThreshold();

    [$classes, $label] = match (true) {
        $stock === 0 => ['bg-danger-soft text-danger ring-1 ring-danger-line', 'Out of stock'],
        $stock <= $threshold => ['bg-warn-soft text-warn ring-1 ring-warn-line', $stock . ' left'],
        default => ['bg-success-soft text-success ring-1 ring-success-line', $stock . ' in stock'],
    };
@endphp

<span class="badge {{ $classes }}">{{ $label }}</span>
