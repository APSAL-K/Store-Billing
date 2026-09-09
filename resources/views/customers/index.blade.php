@extends('layouts.app')

@section('title', 'Customers')

@section('content')
    <x-page-header title="Customers" description="Everyone who has bought from the counter, by lifetime value.">
        <x-slot:actions>
            <a href="{{ route('billing.index') }}" class="btn-primary">New order</a>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" action="{{ route('customers.index') }}" class="card mb-5 flex flex-wrap items-end gap-3 p-4">
        <x-field label="Search" for="search" class="min-w-64 flex-1">
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
                    </svg>
                </span>
                <input id="search" name="search" type="search" value="{{ $search }}" autocomplete="off"
                       placeholder="Name or email" class="field-input pl-9">
            </div>
        </x-field>

        <button type="submit" class="btn-primary">Search</button>

        @if ($search !== '')
            <a href="{{ route('customers.index') }}" class="btn-ghost">Clear</a>
        @endif
    </form>

    <div class="card overflow-hidden">
        @if ($customers->isEmpty())
            <x-empty-state title="No customers found" description="Customers are created the first time they buy.">
                <x-slot:icon>
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    </svg>
                </x-slot:icon>
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[42rem] text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs tracking-wide text-slate-500 uppercase">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Customer</th>
                            <th class="w-28 px-3 py-3 text-right font-semibold">Bills</th>
                            <th class="w-40 px-3 py-3 text-right font-semibold">Lifetime value</th>
                            <th class="w-44 px-5 py-3 text-right font-semibold">Last bill</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($customers as $customer)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-5 py-3 whitespace-nowrap">
                                    <a href="{{ route('customers.show', $customer) }}"
                                       class="block font-medium text-slate-800 hover:text-brand-700 hover:underline">
                                        {{ $customer->name }}
                                    </a>
                                    <span class="block text-xs text-slate-400">{{ $customer->email }}</span>
                                </td>
                                <td class="tnum px-3 py-3 text-right text-slate-600">{{ $customer->orders_count }}</td>
                                <td class="tnum px-3 py-3 text-right font-semibold text-slate-900">
                                    ₹{{ number_format((float) $customer->lifetime_value, 2) }}
                                </td>
                                <td class="px-5 py-3 text-right text-slate-500">
                                    {{ $customer->last_order_at
                                        ? \Illuminate\Support\Carbon::parse($customer->last_order_at)->format('d M Y')
                                        : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($customers->hasPages())
                <div class="border-t border-slate-200 px-5 py-3">{{ $customers->links() }}</div>
            @endif
        @endif
    </div>
@endsection
