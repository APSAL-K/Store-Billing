@props(['product'])

@php
    $stock = $product->stock_on_hand;
    $threshold = $product->effectiveLowStockThreshold();

    [$classes, $label] = match (true) {
        $stock === 0 => ['bg-rose-50 text-rose-700 ring-1 ring-rose-200', 'Out of stock'],
        $stock <= $threshold => ['bg-amber-50 text-amber-700 ring-1 ring-amber-200', $stock . ' left'],
        default => ['bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200', $stock . ' in stock'],
    };
@endphp

<span class="badge {{ $classes }}">{{ $label }}</span>
