@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $today = $headline['today'];
        $yesterday = $headline['yesterday'];
        $peak = max($trend->max('revenue'), 1);
        $hourPeak = max($byHour->max('revenue'), 1);
        $bestSellerPeak = max($bestSellers->max('units_sold') ?? 1, 1);
    @endphp

    <x-page-header title="Dashboard" description="How the counter is trading today.">
        <x-slot:actions>
            <a href="{{ route('orders.index') }}" class="btn-ghost">All bills</a>
            <a href="{{ route('billing.index') }}" class="btn-primary">New order</a>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card p-4">
            <div class="flex items-start justify-between gap-2">
                <p class="text-xs font-medium tracking-wide text-slate-500 uppercase">Revenue today</p>
                <x-trend :change="$headline['change']['revenue']" />
            </div>
            <p class="tnum mt-1.5 text-2xl font-semibold text-brand-700">
                ₹{{ number_format($today['revenue'], 2) }}
            </p>
            <p class="tnum mt-0.5 text-xs text-slate-400">
                Yesterday ₹{{ number_format($yesterday['revenue'], 2) }}
            </p>
        </div>

        <div class="card p-4">
            <div class="flex items-start justify-between gap-2">
                <p class="text-xs font-medium tracking-wide text-slate-500 uppercase">Bills today</p>
                <x-trend :change="$headline['change']['orders']" />
            </div>
            <p class="tnum mt-1.5 text-2xl font-semibold text-slate-900">{{ number_format($today['orders']) }}</p>
            <p class="tnum mt-0.5 text-xs text-slate-400">{{ number_format($today['units']) }} units sold</p>
        </div>

        <div class="card p-4">
            <div class="flex items-start justify-between gap-2">
                <p class="text-xs font-medium tracking-wide text-slate-500 uppercase">Average bill</p>
                <x-trend :change="$headline['change']['average']" />
            </div>
            <p class="tnum mt-1.5 text-2xl font-semibold text-slate-900">
                ₹{{ number_format($today['average'], 2) }}
            </p>
            <p class="tnum mt-0.5 text-xs text-slate-400">
                All time ₹{{ number_format($allTime['average'], 2) }}
            </p>
        </div>

        <div class="card p-4">
            <div class="flex items-start justify-between gap-2">
                <p class="text-xs font-medium tracking-wide text-slate-500 uppercase">Tax collected</p>
                <span class="badge bg-slate-100 text-slate-500">today</span>
            </div>
            <p class="tnum mt-1.5 text-2xl font-semibold text-slate-900">₹{{ number_format($today['tax'], 2) }}</p>
            <p class="tnum mt-0.5 text-xs text-slate-400">
                All time ₹{{ number_format($allTime['tax'], 2) }}
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        <section class="card p-5 lg:col-span-2">
            <div class="mb-5 flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="text-sm font-semibold text-slate-900">Revenue, last 14 days</h2>
                <p class="tnum text-xs text-slate-500">
                    ₹{{ number_format($trend->sum('revenue'), 2) }} across
                    {{ $trend->sum('orders') }} bills
                </p>
            </div>

            <div class="flex h-52 gap-1.5" role="img"
                 aria-label="Daily revenue over the last fourteen days">
                @foreach ($trend as $day)
                    <div class="group relative flex h-full flex-1 flex-col items-center justify-end">
                        <div class="pointer-events-none absolute bottom-full z-10 mb-1.5 hidden whitespace-nowrap rounded-md bg-ink-900 px-2 py-1 text-xs text-white shadow-lg group-hover:block">
                            <span class="tnum font-semibold">₹{{ number_format($day['revenue'], 2) }}</span>
                            <span class="text-slate-400">&middot; {{ $day['orders'] }} bills</span>
                            <span class="block text-[10px] text-slate-400">{{ $day['date']->format('D d M') }}</span>
                        </div>
                        <div class="w-full rounded-t transition
                                    {{ $day['revenue'] > 0
                                        ? 'bg-linear-to-t from-brand-600 to-brand-400 group-hover:from-brand-700 group-hover:to-brand-500'
                                        : 'bg-slate-200' }}"
                             style="height: {{ max(2, round(($day['revenue'] / $peak) * 100)) }}%"></div>
                    </div>
                @endforeach
            </div>

            <div class="mt-2 flex gap-1.5">
                @foreach ($trend as $day)
                    <p class="flex-1 text-center text-[10px] {{ $day['date']->isToday() ? 'font-bold text-brand-700' : 'text-slate-400' }}">
                        {{ $day['date']->format('j') }}
                    </p>
                @endforeach
            </div>
        </section>

        <section class="card p-5">
            <h2 class="text-sm font-semibold text-slate-900">Stock on the shelves</h2>

            <p class="tnum mt-3 text-3xl font-semibold text-slate-900">
                ₹{{ number_format($inventory['retail'], 0) }}
            </p>
            <p class="text-xs text-slate-400">retail value of {{ number_format($inventory['units']) }} units</p>

            @php
                $healthy = max($inventory['products'] - $inventory['low'], 0);
                $lowOnly = max($inventory['low'] - $inventory['out'], 0);
                $total = max($inventory['products'], 1);
            @endphp

            <div class="mt-5 flex h-2.5 overflow-hidden rounded-full bg-slate-100">
                <div class="bg-emerald-500" style="width: {{ ($healthy / $total) * 100 }}%"></div>
                <div class="bg-amber-400" style="width: {{ ($lowOnly / $total) * 100 }}%"></div>
                <div class="bg-rose-500" style="width: {{ ($inventory['out'] / $total) * 100 }}%"></div>
            </div>

            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex items-center gap-2">
                    <span class="size-2 shrink-0 rounded-full bg-emerald-500"></span>
                    <dt class="flex-1 text-slate-600">Healthy</dt>
                    <dd class="tnum font-semibold text-slate-900">{{ $healthy }}</dd>
                </div>
                <div class="flex items-center gap-2">
                    <span class="size-2 shrink-0 rounded-full bg-amber-400"></span>
                    <dt class="flex-1 text-slate-600">Low on stock</dt>
                    <dd class="tnum font-semibold text-slate-900">{{ $lowOnly }}</dd>
                </div>
                <div class="flex items-center gap-2">
                    <span class="size-2 shrink-0 rounded-full bg-rose-500"></span>
                    <dt class="flex-1 text-slate-600">Out of stock</dt>
                    <dd class="tnum font-semibold text-slate-900">{{ $inventory['out'] }}</dd>
                </div>
            </dl>

            <a href="{{ route('products.index', ['low_stock' => 1]) }}"
               class="mt-4 block text-xs font-medium text-brand-700 hover:underline">
                What needs reordering &rarr;
            </a>
        </section>

        <section class="card p-5 lg:col-span-2">
            <div class="mb-4 flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="text-sm font-semibold text-slate-900">Busiest hours</h2>
                <p class="text-xs text-slate-500">Revenue by hour, last 30 days</p>
            </div>

            <div class="flex h-28 items-end gap-1" role="img" aria-label="Revenue by hour of day">
                @foreach ($byHour as $slot)
                    <div class="group relative flex h-full flex-1 flex-col items-center justify-end">
                        <div class="pointer-events-none absolute bottom-full z-10 mb-1.5 hidden whitespace-nowrap rounded-md bg-ink-900 px-2 py-1 text-xs text-white shadow-lg group-hover:block">
                            <span class="tnum">{{ $slot['hour'] }}:00</span>
                            &middot;
                            <span class="tnum font-semibold">₹{{ number_format($slot['revenue'], 0) }}</span>
                        </div>
                        <div class="w-full rounded-sm transition
                                    {{ $slot['revenue'] > 0 ? 'bg-brand-300 group-hover:bg-brand-500' : 'bg-slate-100' }}"
                             style="height: {{ max(3, round(($slot['revenue'] / $hourPeak) * 100)) }}%"></div>
                    </div>
                @endforeach
            </div>

            <div class="mt-1.5 flex gap-1">
                @foreach ($byHour as $slot)
                    <p class="flex-1 text-center text-[9px] text-slate-400">
                        {{ $slot['hour'] % 3 === 0 ? $slot['hour'] : '' }}
                    </p>
                @endforeach
            </div>
        </section>

        <section class="card overflow-hidden">
            <div class="flex items-center gap-2 border-b border-amber-200 bg-amber-50 px-4 py-3">
                <svg class="size-4 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <path d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/>
                </svg>
                <h2 class="text-sm font-semibold text-amber-900">Reorder soon</h2>
                <span class="badge ml-auto bg-amber-100 text-amber-800">{{ $inventory['low'] }}</span>
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

        <section class="card overflow-hidden">
            <h2 class="border-b border-slate-200 px-4 py-3.5 text-sm font-semibold text-slate-900">Best sellers</h2>

            @forelse ($bestSellers as $product)
                <a href="{{ route('products.show', $product) }}"
                   class="block border-b border-slate-100 px-4 py-2.5 transition last:border-0 hover:bg-slate-50">
                    <span class="flex items-baseline gap-3">
                        <span class="min-w-0 flex-1 truncate text-sm text-slate-700">{{ $product->name }}</span>
                        <span class="tnum shrink-0 text-sm font-semibold text-slate-900">{{ $product->units_sold }}</span>
                    </span>
                    <span class="mt-1.5 flex items-center gap-2">
                        <span class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100">
                            <span class="block h-full rounded-full bg-brand-500"
                                  style="width: {{ ($product->units_sold / $bestSellerPeak) * 100 }}%"></span>
                        </span>
                        <span class="tnum shrink-0 text-[11px] text-slate-400">
                            ₹{{ number_format((float) $product->revenue, 0) }}
                        </span>
                    </span>
                </a>
            @empty
                <p class="px-4 py-8 text-center text-sm text-slate-400">Nothing sold yet.</p>
            @endforelse
        </section>

        <section class="card overflow-hidden">
            <h2 class="border-b border-slate-200 px-4 py-3.5 text-sm font-semibold text-slate-900">Top customers</h2>

            @forelse ($topCustomers as $customer)
                <a href="{{ route('customers.show', $customer) }}"
                   class="flex items-center gap-3 border-b border-slate-100 px-4 py-2.5 transition last:border-0 hover:bg-slate-50">
                    <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-slate-200 text-xs font-semibold text-slate-600">
                        {{ mb_strtoupper(mb_substr($customer->name, 0, 1)) }}
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm text-slate-700">{{ $customer->name }}</span>
                        <span class="tnum block text-xs text-slate-400">{{ $customer->orders_count }} bills</span>
                    </span>
                    <span class="tnum shrink-0 text-sm font-semibold text-slate-900">
                        ₹{{ number_format((float) $customer->lifetime_value, 0) }}
                    </span>
                </a>
            @empty
                <p class="px-4 py-8 text-center text-sm text-slate-400">No customers yet.</p>
            @endforelse
        </section>

        <section class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3.5">
                <h2 class="text-sm font-semibold text-slate-900">Recent bills</h2>
                <a href="{{ route('orders.index') }}" class="text-xs font-medium text-brand-700 hover:underline">
                    View all &rarr;
                </a>
            </div>

            @forelse ($recentOrders as $order)
                <a href="{{ route('orders.show', $order) }}"
                   class="flex items-center gap-3 border-b border-slate-100 px-4 py-2.5 transition last:border-0 hover:bg-slate-50">
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm text-slate-700">{{ $order->customer->name }}</span>
                        <span class="block font-mono text-[11px] text-slate-400">{{ $order->reference }}</span>
                    </span>
                    <span class="shrink-0 text-right">
                        <span class="tnum block text-sm font-semibold text-slate-900">
                            ₹{{ number_format((float) $order->grand_total, 2) }}
                        </span>
                        <span class="block text-[11px] text-slate-400">{{ $order->placed_at->diffForHumans(short: true) }}</span>
                    </span>
                </a>
            @empty
                <p class="px-4 py-8 text-center text-sm text-slate-400">No bills raised yet.</p>
            @endforelse
        </section>

        <section class="card overflow-hidden lg:col-span-3">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3.5">
                <h2 class="text-sm font-semibold text-slate-900">Stock activity</h2>
                <p class="text-xs text-slate-500">Every movement on the shelves, newest first</p>
            </div>

            @forelse ($movements as $movement)
                @php
                    $up = $movement->quantity_change > 0;
                @endphp
                <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-2.5 last:border-0">
                    <span class="flex size-7 shrink-0 items-center justify-center rounded-full
                                 {{ $up ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="{{ $up ? 'M12 19V5m0 0-6 6m6-6 6 6' : 'M12 5v14m0 0 6-6m-6 6-6-6' }}"/>
                        </svg>
                    </span>

                    <a href="{{ route('products.show', $movement->product) }}"
                       class="min-w-0 flex-1 truncate text-sm text-slate-700 hover:text-brand-700 hover:underline">
                        {{ $movement->product->name }}
                    </a>

                    <span class="hidden shrink-0 text-xs text-slate-500 sm:block">{{ $movement->label() }}</span>

                    @if ($movement->order)
                        <a href="{{ route('orders.show', $movement->order) }}"
                           class="hidden shrink-0 font-mono text-[11px] text-brand-700 hover:underline md:block">
                            {{ $movement->order->reference }}
                        </a>
                    @else
                        <span class="hidden w-[9.5rem] shrink-0 md:block"></span>
                    @endif

                    <span class="tnum w-14 shrink-0 text-right text-sm font-semibold
                                 {{ $up ? 'text-emerald-600' : 'text-rose-600' }}">
                        {{ $up ? '+' : '' }}{{ $movement->quantity_change }}
                    </span>
                    <span class="tnum w-20 shrink-0 text-right text-xs text-slate-400">
                        {{ $movement->created_at->diffForHumans(short: true) }}
                    </span>
                </div>
            @empty
                <p class="px-5 py-8 text-center text-sm text-slate-400">No stock has moved yet.</p>
            @endforelse
        </section>
    </div>
@endsection
