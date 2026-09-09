@extends('layouts.app')

@section('title', 'Orders')

@section('content')
    <x-page-header title="Orders" description="Every bill raised at the counter, most recent first.">
        <x-slot:actions>
            <a href="{{ route('billing.index') }}" class="btn-primary">New order</a>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" action="{{ route('orders.index') }}" class="card mb-5 flex flex-wrap items-end gap-3 p-4">
        <x-field label="Customer email" for="email" class="min-w-64 flex-1">
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
                    </svg>
                </span>
                <input id="email" name="email" type="search" value="{{ $email }}" autocomplete="off"
                       placeholder="thomas@example.com" class="field-input pl-9">
            </div>
        </x-field>

        <label class="flex cursor-pointer items-center gap-2 pb-2.5 text-sm text-slate-600">
            <input type="checkbox" name="voided" value="1" @checked($showingVoided)
                   class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
            Voided only
            @if ($voidedCount > 0)
                <span class="badge bg-rose-50 text-rose-700 ring-1 ring-rose-200">{{ $voidedCount }}</span>
            @endif
        </label>

        <button type="submit" class="btn-primary">Search</button>

        @if ($email !== '' || $showingVoided)
            <a href="{{ route('orders.index') }}" class="btn-ghost">Clear</a>
        @endif
    </form>

    <div class="card overflow-hidden">
        @if ($orders->isEmpty())
            <x-empty-state
                title="No orders found"
                :description="$email !== ''
                    ? 'Nothing matches that email yet.'
                    : 'Bills raised at the counter will appear here.'">
                <x-slot:icon>
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>
                    </svg>
                </x-slot:icon>
                <x-slot:action>
                    <a href="{{ route('billing.index') }}" class="btn-primary">Raise the first bill</a>
                </x-slot:action>
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[46rem] text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs tracking-wide text-slate-500 uppercase">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Bill</th>
                            <th class="px-3 py-3 font-semibold">Customer</th>
                            <th class="w-24 px-3 py-3 text-right font-semibold">Items</th>
                            <th class="w-32 px-3 py-3 text-right font-semibold">Tax</th>
                            <th class="w-36 px-3 py-3 text-right font-semibold">Total</th>
                            <th class="w-40 px-3 py-3 text-right font-semibold">Placed</th>
                            <th class="w-28 px-5 py-3 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($orders as $order)
                            <tr class="transition hover:bg-slate-50 {{ $order->trashed() ? 'opacity-55' : '' }}">
                                <td class="px-5 py-3 whitespace-nowrap">
                                    <a href="{{ route('orders.show', $order) }}"
                                       class="font-mono text-xs font-semibold text-brand-700 hover:underline">
                                        {{ $order->reference }}
                                    </a>
                                    @if ($order->trashed())
                                        <span class="badge ml-1.5 bg-rose-50 text-rose-700 ring-1 ring-rose-200">Voided</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <span class="block text-slate-800">{{ $order->customer->name }}</span>
                                    <span class="block text-xs text-slate-400">{{ $order->customer->email }}</span>
                                </td>
                                <td class="tnum px-3 py-3 text-right text-slate-600">{{ $order->items_sum_quantity }}</td>
                                <td class="tnum px-3 py-3 text-right text-slate-500">₹{{ number_format((float) $order->tax_total, 2) }}</td>
                                <td class="tnum px-3 py-3 text-right font-semibold text-slate-900">₹{{ number_format((float) $order->grand_total, 2) }}</td>
                                <td class="px-3 py-3 text-right">
                                    <span class="block text-slate-600">{{ $order->placed_at->format('d M Y') }}</span>
                                    <span class="tnum block text-xs text-slate-400">{{ $order->placed_at->format('g:i A') }}</span>
                                </td>
                                <td class="px-5 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('orders.show', $order) }}"
                                       class="text-xs font-medium text-slate-500 hover:text-slate-900">View</a>
                                    @unless ($order->trashed())
                                        <a href="{{ route('billing.edit', $order) }}"
                                           class="ml-2 text-xs font-medium text-brand-700 hover:underline">Edit</a>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($orders->hasPages())
                <div class="border-t border-slate-200 px-5 py-3">
                    {{ $orders->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection
