@extends('layouts.app')

@section('title', 'Inventory')

@section('content')
    <x-page-header title="Inventory" description="Catalogue and stock on hand across the counter.">
        <x-slot:actions>
            <a href="{{ route('billing.index') }}" class="btn-primary">New order</a>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Products" :value="$stats['total']" />
        <x-stat label="Units on hand" :value="number_format($stats['units'])" tone="brand" />
        <x-stat label="Low on stock" :value="$stats['low']" tone="amber" caption="At or below threshold" />
        <x-stat label="Out of stock" :value="$stats['out']" tone="rose" caption="Cannot be sold" />
    </div>

    <form method="GET" action="{{ route('products.index') }}" class="card mb-5 flex flex-wrap items-end gap-3 p-4">
        <x-field label="Search" for="search" class="min-w-64 flex-1">
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
                    </svg>
                </span>
                <input id="search" name="search" type="search" value="{{ $search }}" autocomplete="off"
                       placeholder="Name or product code" class="field-input pl-9">
            </div>
        </x-field>

        <label class="flex cursor-pointer items-center gap-2 pb-2.5 text-sm text-slate-600">
            <input type="checkbox" name="low_stock" value="1" @checked($onlyLowStock)
                   class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
            Only low stock
        </label>

        <button type="submit" class="btn-primary">Filter</button>

        @if ($search !== '' || $onlyLowStock)
            <a href="{{ route('products.index') }}" class="btn-ghost">Reset</a>
        @endif
    </form>

    <div class="card overflow-hidden">
        @if ($products->isEmpty())
            <x-empty-state title="No products match" description="Try a different search, or clear the filters.">
                <x-slot:icon>
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m21 8-9-5-9 5 9 5 9-5Zm0 8-9 5-9-5m18-4-9 5-9-5"/>
                    </svg>
                </x-slot:icon>
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[44rem] text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs tracking-wide text-slate-500 uppercase">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Product</th>
                            <th class="w-32 px-3 py-3 font-semibold">Code</th>
                            <th class="w-28 px-3 py-3 text-right font-semibold">Price</th>
                            <th class="w-20 px-3 py-3 text-right font-semibold">Tax</th>
                            <th class="w-28 px-3 py-3 text-right font-semibold">Threshold</th>
                            <th class="w-36 px-5 py-3 text-right font-semibold">Stock</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($products as $product)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-5 py-3 font-medium whitespace-nowrap text-slate-800">{{ $product->name }}</td>
                                <td class="px-3 py-3 font-mono text-xs text-slate-400">{{ $product->code }}</td>
                                <td class="tnum px-3 py-3 text-right text-slate-700">₹{{ number_format((float) $product->unit_price, 2) }}</td>
                                <td class="tnum px-3 py-3 text-right text-slate-500">{{ rtrim(rtrim(number_format((float) $product->tax_percentage, 2), '0'), '.') }}%</td>
                                <td class="tnum px-3 py-3 text-right text-slate-500">
                                    {{ $product->effectiveLowStockThreshold() }}
                                    @unless ($product->low_stock_threshold)
                                        <span class="ml-0.5 text-xs text-slate-300" title="Falls back to the application default">def</span>
                                    @endunless
                                </td>
                                <td class="px-5 py-3 text-right"><x-stock-badge :product="$product" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($products->hasPages())
                <div class="border-t border-slate-200 px-5 py-3">
                    {{ $products->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection
