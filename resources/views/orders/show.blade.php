@extends('layouts.app')

@section('title', $order->reference)

@section('content')
    @php($breakdown = $order->change_due === null
        ? []
        : \App\Support\CashDrawer::breakdown(\App\Support\Money::toMinor($order->change_due)))

    <div class="mx-auto max-w-3xl" x-data="deleteOrder">

        @if ($order->trashed())
            <div class="no-print mb-5 flex items-start gap-3 rounded-xl border border-danger-line bg-danger-soft p-4">
                <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-danger text-white">
                    <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
                         stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                </span>
                <div>
                    <p class="text-sm font-semibold text-danger">This bill was deleted</p>
                    <p class="mt-0.5 text-sm text-danger">
                        Its stock went back on the shelf on {{ $order->deleted_at->format('d M Y, g:i A') }}.
                        The record is kept so the ledger still adds up.
                    </p>
                </div>
            </div>
        @endif

        @if (request()->boolean('updated'))
            <div class="no-print mb-5 flex items-start gap-3 rounded-xl border border-success-line bg-success-soft p-4">
                <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-success text-white">
                    <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 13 4 4L19 7"/></svg>
                </span>
                <div>
                    <p class="text-sm font-semibold text-success">Bill updated</p>
                    <p class="mt-0.5 text-sm text-success">
                        Stock has been reconciled against the previous lines and the bill repriced.
                    </p>
                </div>
            </div>
        @endif

        @if (request()->boolean('placed'))
            <div class="no-print mb-5 flex items-start gap-3 rounded-xl border border-success-line bg-success-soft p-4">
                <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-success text-white">
                    <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 13 4 4L19 7"/></svg>
                </span>
                <div>
                    <p class="text-sm font-semibold text-success">Bill saved</p>
                    <p class="mt-0.5 text-sm text-success">
                        Stock has been deducted and the confirmation email is queued to
                        {{ $order->customer->email }}.
                    </p>
                </div>
            </div>
        @endif

        <div class="no-print mb-5 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('orders.index') }}"
               class="inline-flex items-center gap-1.5 text-sm font-medium text-muted transition hover:text-ink">
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
                    <button type="button" class="btn-ghost text-danger hover:bg-danger-soft"
                            @click="confirm(@js($deleteTarget))">
                        Delete bill
                    </button>
                @endunless
                <a href="{{ route('billing.index') }}" class="btn-primary">New order</a>
            </div>
        </div>

        <article class="card p-6 sm:p-8 {{ $order->trashed() ? 'opacity-70' : '' }}">
            <header class="flex flex-wrap items-start justify-between gap-4 border-b border-line pb-5">
                <div>
                    <p class="text-xs font-semibold tracking-wide text-faint uppercase">Tax invoice</p>
                    <p class="mt-1 font-mono text-lg font-semibold text-ink">{{ $order->reference }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold text-ink">{{ config('app.name') }}</p>
                    <p class="tnum mt-0.5 text-xs text-muted">
                        {{ $order->placed_at->format('d M Y, g:i A') }}
                    </p>
                </div>
            </header>

            <div class="border-b border-line py-4">
                <p class="text-xs font-semibold tracking-wide text-faint uppercase">Billed to</p>
                <p class="mt-1 text-sm font-medium text-ink">{{ $order->customer->name }}</p>
                <p class="text-sm text-muted">{{ $order->customer->email }}</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-xs tracking-wide text-faint uppercase">
                        <tr>
                            <th class="py-3 font-semibold">Item</th>
                            <th class="w-16 py-3 text-center font-semibold">Qty</th>
                            <th class="w-28 py-3 text-right font-semibold">Rate</th>
                            <th class="w-28 py-3 text-right font-semibold">Tax</th>
                            <th class="w-32 py-3 text-right font-semibold">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($order->items as $item)
                            <tr>
                                <td class="py-3">
                                    <span class="block font-medium text-ink">{{ $item->product->name }}</span>
                                    <span class="block font-mono text-xs text-faint">{{ $item->product->code }}</span>
                                </td>
                                <td class="tnum py-3 text-center text-body">{{ $item->quantity }}</td>
                                <td class="tnum py-3 text-right text-body">₹{{ number_format((float) $item->unit_price, 2) }}</td>
                                <td class="tnum py-3 text-right text-muted">
                                    ₹{{ number_format((float) $item->line_tax, 2) }}
                                    <span class="text-xs text-faint">
                                        ({{ rtrim(rtrim(number_format((float) $item->tax_percentage, 2), '0'), '.') }}%)
                                    </span>
                                </td>
                                <td class="tnum py-3 text-right font-semibold text-ink">₹{{ number_format((float) $item->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-5 flex justify-end border-t border-line pt-5">
                <dl class="w-full max-w-xs space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-muted">Subtotal</dt>
                        <dd class="tnum text-body">₹{{ number_format((float) $order->subtotal, 2) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">Tax</dt>
                        <dd class="tnum text-body">₹{{ number_format((float) $order->tax_total, 2) }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between border-t border-line pt-2">
                        <dt class="font-semibold text-ink">Grand total</dt>
                        <dd class="tnum text-xl font-semibold text-ink">₹{{ number_format((float) $order->grand_total, 2) }}</dd>
                    </div>

                    @if ($order->amount_tendered !== null)
                        <div class="flex justify-between border-t border-dashed border-line pt-2 text-muted">
                            <dt>Cash received</dt>
                            <dd class="tnum">₹{{ number_format((float) $order->amount_tendered, 2) }}</dd>
                        </div>
                        <div class="flex justify-between font-medium text-success">
                            <dt>Balance returned</dt>
                            <dd class="tnum">₹{{ number_format((float) $order->change_due, 2) }}</dd>
                        </div>
                        @if ($breakdown !== [])
                            <div class="flex flex-wrap justify-end gap-1.5 pt-1">
                                @foreach ($breakdown as $part)
                                    <span class="badge tnum bg-sunken text-muted">
                                        {{ $part['count'] }} &times; ₹{{ $part['denomination'] }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    @endif
                </dl>
            </div>

            <p class="mt-8 border-t border-line pt-4 text-center text-xs text-faint">
                Thank you for shopping with us.
            </p>
        </article>

        <x-modal title="Delete this bill?" width="max-w-sm">
            <p class="mt-1 text-sm text-muted">
                <span class="font-mono font-semibold text-body">{{ $order->reference }}</span> is removed
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
