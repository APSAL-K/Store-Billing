@extends('layouts.app')

@section('title', $order->reference)
@section('subtitle', 'Bill ' . $order->reference)

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="rounded-lg border border-slate-300 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between border-b border-slate-200 pb-4">
                <div>
                    <p class="text-lg font-semibold text-slate-900">{{ $order->reference }}</p>
                    <p class="text-sm text-slate-600">{{ $order->customer->name }} &middot; {{ $order->customer->email }}</p>
                </div>
                <p class="text-right text-xs text-slate-500">{{ $order->placed_at->format('d M Y, g:i A') }}</p>
            </div>

            <table class="mt-4 w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="pb-2 font-semibold">Item</th>
                        <th class="pb-2 text-center font-semibold">Qty</th>
                        <th class="pb-2 text-right font-semibold">Rate</th>
                        <th class="pb-2 text-right font-semibold">Tax</th>
                        <th class="pb-2 text-right font-semibold">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="py-2">
                                <span class="text-slate-800">{{ $item->product->name }}</span>
                                <span class="ml-1 text-xs text-slate-500">{{ $item->product->code }}</span>
                            </td>
                            <td class="py-2 text-center tabular-nums">{{ $item->quantity }}</td>
                            <td class="py-2 text-right tabular-nums">₹{{ number_format((float) $item->unit_price, 2) }}</td>
                            <td class="py-2 text-right tabular-nums text-slate-600">
                                ₹{{ number_format((float) $item->line_tax, 2) }}
                                <span class="text-xs text-slate-400">({{ rtrim(rtrim((string) $item->tax_percentage, '0'), '.') }}%)</span>
                            </td>
                            <td class="py-2 text-right font-medium tabular-nums">₹{{ number_format((float) $item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <dl class="mt-4 space-y-1.5 border-t border-slate-200 pt-4 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-600">Subtotal</dt>
                    <dd class="tabular-nums">₹{{ number_format((float) $order->subtotal, 2) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-600">Tax</dt>
                    <dd class="tabular-nums">₹{{ number_format((float) $order->tax_total, 2) }}</dd>
                </div>
                <div class="flex justify-between border-t border-slate-200 pt-1.5 text-base font-semibold">
                    <dt>Grand Total</dt>
                    <dd class="tabular-nums">₹{{ number_format((float) $order->grand_total, 2) }}</dd>
                </div>

                @if ($order->amount_tendered !== null)
                    @php($breakdown = \App\Support\CashDrawer::breakdown(\App\Support\Money::toMinor($order->change_due)))
                    <div class="flex justify-between border-t border-dashed border-slate-300 pt-2 text-slate-600">
                        <dt>Cash Received</dt>
                        <dd class="tabular-nums">₹{{ number_format((float) $order->amount_tendered, 2) }}</dd>
                    </div>
                    <div class="flex justify-between font-medium">
                        <dt>Balance Returned</dt>
                        <dd class="tabular-nums">
                            ₹{{ number_format((float) $order->change_due, 2) }}
                            @if ($breakdown !== [])
                                <span class="ml-2 text-xs font-normal text-slate-500">
                                    {{ collect($breakdown)->map(fn ($part) => "{$part['count']}×{$part['denomination']}")->implode(' + ') }}
                                </span>
                            @endif
                        </dd>
                    </div>
                @endif
            </dl>
        </div>

        <div class="mt-4 flex items-center justify-between text-sm">
            <a href="{{ route('billing.index') }}" class="font-medium text-slate-700 underline underline-offset-4 hover:text-slate-900">
                Start another order
            </a>
            <p class="text-xs text-slate-500">Confirmation email queued to {{ $order->customer->email }}</p>
        </div>
    </div>
@endsection
