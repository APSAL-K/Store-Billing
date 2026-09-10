@extends('layouts.app')

@section('title', $customer->name)

@section('content')
    <x-page-header :title="$customer->name" :description="$customer->email">
        <x-slot:actions>
            <a href="{{ route('customers.index') }}" class="btn-ghost">All customers</a>
            <a href="{{ route('billing.index') }}" class="btn-primary">New order</a>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-3">
        <x-stat label="Bills" :value="$customer->orders_count" />
        <x-stat label="Lifetime value" value="₹{{ number_format($lifetimeValue, 2) }}" tone="brand" />
        <x-stat label="Average bill"
                value="₹{{ number_format($customer->orders_count > 0 ? $lifetimeValue / $customer->orders_count : 0, 2) }}" />
    </div>

    <div class="card overflow-hidden">
        <h2 class="border-b border-line px-5 py-3.5 text-sm font-semibold text-ink">Order history</h2>

        @if ($orders->isEmpty())
            <x-empty-state title="Nothing bought yet" description="Bills raised for this customer will appear here." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[42rem] text-sm">
                    <thead class="border-b border-line bg-raised text-left text-xs tracking-wide text-muted uppercase">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Bill</th>
                            <th class="w-24 px-3 py-3 text-right font-semibold">Items</th>
                            <th class="w-32 px-3 py-3 text-right font-semibold">Total</th>
                            <th class="w-44 px-5 py-3 text-right font-semibold">Placed</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($orders as $order)
                            <tr class="transition hover:bg-raised {{ $order->trashed() ? 'opacity-55' : '' }}">
                                <td class="px-5 py-3 whitespace-nowrap">
                                    <a href="{{ route('orders.show', $order) }}"
                                       class="font-mono text-xs font-semibold text-brand-700 hover:underline">
                                        {{ $order->reference }}
                                    </a>
                                    @if ($order->trashed())
                                        <span class="badge ml-1.5 bg-danger-soft text-danger ring-1 ring-danger-line">Deleted</span>
                                    @endif
                                </td>
                                <td class="tnum px-3 py-3 text-right text-body">{{ $order->items_sum_quantity }}</td>
                                <td class="tnum px-3 py-3 text-right font-semibold text-ink">
                                    ₹{{ number_format((float) $order->grand_total, 2) }}
                                </td>
                                <td class="px-5 py-3 text-right text-muted">{{ $order->placed_at->format('d M Y, g:i A') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-pagination :paginator="$orders" label="bills" />
        @endif
    </div>
@endsection
