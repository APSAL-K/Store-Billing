@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $peak = max($trend->max('revenue'), 1);
    @endphp

    <x-page-header title="Dashboard" description="How the counter is trading today.">
        <x-slot:actions>
            <a href="{{ route('billing.index') }}" class="btn-primary">New order</a>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Revenue today" value="₹{{ number_format($today['revenue'], 2) }}" tone="brand"
                caption="{{ $today['orders'] }} {{ Str::plural('bill', $today['orders']) }}" />
        <x-stat label="Units sold today" :value="number_format($today['units'])" />
        <x-stat label="Tax collected today" value="₹{{ number_format($today['tax'], 2) }}" />
        <x-stat label="Low on stock" :value="$lowStock->count()" tone="amber" caption="Needs reordering" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        <section class="card p-5 lg:col-span-2">
            <div class="mb-5 flex items-baseline justify-between">
                <h2 class="text-sm font-semibold text-slate-900">Revenue, last 14 days</h2>
                <p class="text-xs text-slate-500">
                    All time ₹{{ number_format($allTime['revenue'], 2) }} across
                    {{ $allTime['orders'] }} bills
                </p>
            </div>

            <div class="flex h-48 gap-1.5" role="img"
                 aria-label="Bar chart of daily revenue over the last fourteen days">
                @foreach ($trend as $day)
                    <div class="group relative flex h-full flex-1 flex-col items-center justify-end">
                        <div class="pointer-events-none absolute bottom-full z-10 mb-1.5 hidden whitespace-nowrap rounded-md bg-ink-900 px-2 py-1 text-xs text-white group-hover:block">
                            ₹{{ number_format($day['revenue'], 2) }}
                            <span class="text-slate-400">&middot; {{ $day['orders'] }} bills</span>
                        </div>
                        <div class="w-full rounded-t transition
                                    {{ $day['revenue'] > 0 ? 'bg-brand-500 group-hover:bg-brand-600' : 'bg-slate-200' }}"
                             style="height: {{ max(2, round(($day['revenue'] / $peak) * 100)) }}%"></div>
                    </div>
                @endforeach
            </div>

            <div class="mt-2 flex gap-1.5">
                @foreach ($trend as $day)
                    <p class="flex-1 text-center text-[10px] text-slate-400">
                        {{ $day['date']->format('j') }}
                    </p>
                @endforeach
            </div>
        </section>

        <section class="card overflow-hidden">
            <h2 class="border-b border-slate-200 px-4 py-3.5 text-sm font-semibold text-slate-900">Best sellers</h2>

            @forelse ($bestSellers as $product)
                <a href="{{ route('products.show', $product) }}"
                   class="flex items-center gap-3 border-b border-slate-100 px-4 py-2.5 transition last:border-0 hover:bg-slate-50">
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm text-slate-700">{{ $product->name }}</span>
                        <span class="block font-mono text-xs text-slate-400">{{ $product->code }}</span>
                    </span>
                    <span class="tnum shrink-0 text-sm font-semibold text-slate-900">
                        {{ $product->units_sold }}
                        <span class="text-xs font-normal text-slate-400">sold</span>
                    </span>
                </a>
            @empty
                <p class="px-4 py-8 text-center text-sm text-slate-400">Nothing sold yet.</p>
            @endforelse
        </section>

        <section class="card overflow-hidden lg:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3.5">
                <h2 class="text-sm font-semibold text-slate-900">Recent bills</h2>
                <a href="{{ route('orders.index') }}" class="text-xs font-medium text-brand-700 hover:underline">
                    View all &rarr;
                </a>
            </div>

            @forelse ($recentOrders as $order)
                <a href="{{ route('orders.show', $order) }}"
                   class="flex items-center gap-4 border-b border-slate-100 px-4 py-3 transition last:border-0 hover:bg-slate-50">
                    <span class="shrink-0 font-mono text-xs font-semibold text-brand-700">{{ $order->reference }}</span>
                    <span class="min-w-0 flex-1 truncate text-sm text-slate-700">{{ $order->customer->name }}</span>
                    <span class="tnum shrink-0 text-xs text-slate-400">{{ $order->placed_at->diffForHumans() }}</span>
                    <span class="tnum shrink-0 text-sm font-semibold text-slate-900">
                        ₹{{ number_format((float) $order->grand_total, 2) }}
                    </span>
                </a>
            @empty
                <p class="px-4 py-8 text-center text-sm text-slate-400">No bills raised yet.</p>
            @endforelse
        </section>

        <section class="card overflow-hidden">
            <div class="flex items-center gap-2 border-b border-amber-200 bg-amber-50 px-4 py-3">
                <svg class="size-4 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <path d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/>
                </svg>
                <h2 class="text-sm font-semibold text-amber-900">Reorder soon</h2>
            </div>

            @forelse ($lowStock as $product)
                <a href="{{ route('products.show', $product) }}"
                   class="flex items-center gap-3 border-b border-slate-100 px-4 py-2.5 transition last:border-0 hover:bg-slate-50">
                    <span class="min-w-0 flex-1 truncate text-sm text-slate-700">{{ $product->name }}</span>
                    <x-stock-badge :product="$product" />
                </a>
            @empty
                <p class="px-4 py-8 text-center text-sm text-slate-400">Everything is above its threshold.</p>
            @endforelse
        </section>
    </div>
@endsection
