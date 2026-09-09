@extends('layouts.app')

@section('title', $order->reference)

@section('content')
    @php($breakdown = $order->change_due === null
        ? []
        : \App\Support\CashDrawer::breakdown(\App\Support\Money::toMinor($order->change_due)))

    <div class="mx-auto max-w-3xl" x-data="deleteOrder">

        @if ($order->trashed())
            <div class="no-print mb-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4">
                <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-rose-500 text-white">
                    <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
                         stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                </span>
                <div>
                    <p class="text-sm font-semibold text-rose-900">This bill was deleted</p>
                    <p class="mt-0.5 text-sm text-rose-700">
                        Its stock went back on the shelf on {{ $order->deleted_at->format('d M Y, g:i A') }}.
                        The record is kept so the ledger still adds up.
                    </p>
                </div>
            </div>
        @endif

        @if (request()->boolean('updated'))
            <div class="no-print mb-5 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white">
                    <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 13 4 4L19 7"/></svg>
                </span>
                <div>
                    <p class="text-sm font-semibold text-emerald-900">Bill updated</p>
                    <p class="mt-0.5 text-sm text-emerald-700">
                        Stock has been reconciled against the previous lines and the bill repriced.
                    </p>
                </div>
            </div>
        @endif

        @if (request()->boolean('placed'))
            <div class="no-print mb-5 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white">
                    <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 13 4 4L19 7"/></svg>
                </span>
                <div>
                    <p class="text-sm font-semibold text-emerald-900">Bill saved</p>
                    <p class="mt-0.5 text-sm text-emerald-700">
                        Stock has been deducted and the confirmation email is queued to
                        {{ $order->customer->email }}.
                    </p>
                </div>
            </div>
        @endif

        <div class="no-print mb-5 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('orders.index') }}"
               class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-800">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 12H5m0 0 7 7m-7-7 7-7"/>
                </svg>
                All orders
            </a>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" onclick="window.print()" class="btn-ghost">Print</button>
                @unless ($order->trashed())
                    <a href="{{ route('billing.edit', $order) }}" class="btn-ghost">Edit</a>
                    @php($deleteTarget = ['id' => $order->id, 'reference' => $order->reference, 'units' => $order->items->sum('quantity'), 'redirect' => route('orders.index')])
                    <button type="button" class="btn-ghost text-rose-700 hover:bg-rose-50"
                            @click="confirm(@js($deleteTarget))">
                        Delete bill
                    </button>
                @endunless
                <a href="{{ route('billing.index') }}" class="btn-primary">New order</a>
            </div>
        </div>

        <article class="card p-6 sm:p-8 {{ $order->trashed() ? 'opacity-70' : '' }}">
            <header class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 pb-5">
                <div>
                    <p class="text-xs font-semibold tracking-wide text-slate-400 uppercase">Tax invoice</p>
                    <p class="mt-1 font-mono text-lg font-semibold text-slate-900">{{ $order->reference }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold text-slate-900">{{ config('app.name') }}</p>
                    <p class="tnum mt-0.5 text-xs text-slate-500">
                        {{ $order->placed_at->format('d M Y, g:i A') }}
                    </p>
                </div>
            </header>

            <div class="border-b border-slate-200 py-4">
                <p class="text-xs font-semibold tracking-wide text-slate-400 uppercase">Billed to</p>
                <p class="mt-1 text-sm font-medium text-slate-800">{{ $order->customer->name }}</p>
                <p class="text-sm text-slate-500">{{ $order->customer->email }}</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-xs tracking-wide text-slate-400 uppercase">
                        <tr>
                            <th class="py-3 font-semibold">Item</th>
                            <th class="w-16 py-3 text-center font-semibold">Qty</th>
                            <th class="w-28 py-3 text-right font-semibold">Rate</th>
                            <th class="w-28 py-3 text-right font-semibold">Tax</th>
                            <th class="w-32 py-3 text-right font-semibold">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($order->items as $item)
                            <tr>
                                <td class="py-3">
                                    <span class="block font-medium text-slate-800">{{ $item->product->name }}</span>
                                    <span class="block font-mono text-xs text-slate-400">{{ $item->product->code }}</span>
                                </td>
                                <td class="tnum py-3 text-center text-slate-600">{{ $item->quantity }}</td>
                                <td class="tnum py-3 text-right text-slate-600">₹{{ number_format((float) $item->unit_price, 2) }}</td>
                                <td class="tnum py-3 text-right text-slate-500">
                                    ₹{{ number_format((float) $item->line_tax, 2) }}
                                    <span class="text-xs text-slate-400">
                                        ({{ rtrim(rtrim(number_format((float) $item->tax_percentage, 2), '0'), '.') }}%)
                                    </span>
                                </td>
                                <td class="tnum py-3 text-right font-semibold text-slate-900">₹{{ number_format((float) $item->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-5 flex justify-end border-t border-slate-200 pt-5">
                <dl class="w-full max-w-xs space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Subtotal</dt>
                        <dd class="tnum text-slate-700">₹{{ number_format((float) $order->subtotal, 2) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Tax</dt>
                        <dd class="tnum text-slate-700">₹{{ number_format((float) $order->tax_total, 2) }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between border-t border-slate-200 pt-2">
                        <dt class="font-semibold text-slate-900">Grand total</dt>
                        <dd class="tnum text-xl font-semibold text-slate-900">₹{{ number_format((float) $order->grand_total, 2) }}</dd>
                    </div>

                    @if ($order->amount_tendered !== null)
                        <div class="flex justify-between border-t border-dashed border-slate-200 pt-2 text-slate-500">
                            <dt>Cash received</dt>
                            <dd class="tnum">₹{{ number_format((float) $order->amount_tendered, 2) }}</dd>
                        </div>
                        <div class="flex justify-between font-medium text-emerald-700">
                            <dt>Balance returned</dt>
                            <dd class="tnum">₹{{ number_format((float) $order->change_due, 2) }}</dd>
                        </div>
                        @if ($breakdown !== [])
                            <div class="flex flex-wrap justify-end gap-1.5 pt-1">
                                @foreach ($breakdown as $part)
                                    <span class="badge tnum bg-slate-100 text-slate-600">
                                        {{ $part['count'] }} &times; ₹{{ $part['denomination'] }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    @endif
                </dl>
            </div>

            <p class="mt-8 border-t border-slate-200 pt-4 text-center text-xs text-slate-400">
                Thank you for shopping with us.
            </p>
        </article>

        <x-modal title="Delete this bill?" width="max-w-sm">
            <p class="mt-1 text-sm text-slate-500">
                <span class="font-mono font-semibold text-slate-700">{{ $order->reference }}</span> is removed
                from the till and its {{ $order->items->sum('quantity') }} units go back on the shelf. The
                record is kept and stays readable under the deleted filter.
            </p>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" class="btn-ghost" @click="open = false">Keep the bill</button>
                <button type="button" class="btn-danger" :disabled="working" @click="run()">
                    <span x-text="working ? 'Deleting…' : 'Delete bill'"></span>
                </button>
            </div>
        </x-modal>
    </div>
@endsection
