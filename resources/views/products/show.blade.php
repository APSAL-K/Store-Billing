@extends('layouts.app')

@section('title', $product->name)

@section('content')
    <div x-data="restockProduct({{ $product->id }})">

        <x-page-header :title="$product->name" :description="$product->code">
            <x-slot:actions>
                <a href="{{ route('products.index') }}" class="btn-ghost">All products</a>
                <button type="button" class="btn-primary" @click="open = true">Add stock</button>
            </x-slot:actions>
        </x-page-header>

        <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <x-stat label="Stock on hand" :value="$product->stock_on_hand"
                    :tone="$product->stock_on_hand <= $product->effectiveLowStockThreshold() ? 'amber' : 'slate'"
                    caption="Threshold {{ $product->effectiveLowStockThreshold() }}" />
            <x-stat label="Unit price" value="₹{{ number_format((float) $product->unit_price, 2) }}" />
            <x-stat label="Tax rate" value="{{ rtrim(rtrim(number_format((float) $product->tax_percentage, 2), '0'), '.') }}%" />
            <x-stat label="Units sold" :value="number_format($sold)" tone="brand" />
        </div>

        <div class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3.5">
                <h2 class="text-sm font-semibold text-slate-900">Stock movements</h2>
                <p class="text-xs text-slate-500">Every change to this product's stock, newest first</p>
            </div>

            @if ($movements->isEmpty())
                <x-empty-state title="No movements yet" description="Sales and restocks will be recorded here." />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[40rem] text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs tracking-wide text-slate-500 uppercase">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Reason</th>
                                <th class="px-3 py-3 font-semibold">Bill</th>
                                <th class="w-28 px-3 py-3 text-right font-semibold">Change</th>
                                <th class="w-28 px-3 py-3 text-right font-semibold">Balance</th>
                                <th class="w-44 px-5 py-3 text-right font-semibold">When</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($movements as $movement)
                                <tr class="transition hover:bg-slate-50">
                                    <td class="px-5 py-3 whitespace-nowrap">
                                        <span class="text-slate-800">{{ $movement->label() }}</span>
                                        @if ($movement->note)
                                            <span class="block text-xs text-slate-400">{{ $movement->note }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3">
                                        @if ($movement->order)
                                            <a href="{{ route('orders.show', $movement->order) }}"
                                               class="font-mono text-xs font-semibold text-brand-700 hover:underline">
                                                {{ $movement->order->reference }}
                                            </a>
                                        @else
                                            <span class="text-xs text-slate-300">&mdash;</span>
                                        @endif
                                    </td>
                                    <td class="tnum px-3 py-3 text-right font-semibold
                                               {{ $movement->quantity_change < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                        {{ $movement->quantity_change > 0 ? '+' : '' }}{{ $movement->quantity_change }}
                                    </td>
                                    <td class="tnum px-3 py-3 text-right text-slate-600">{{ $movement->balance_after }}</td>
                                    <td class="px-5 py-3 text-right text-slate-500">{{ $movement->created_at->format('d M Y, g:i A') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($movements->hasPages())
                    <div class="border-t border-slate-200 px-5 py-3">{{ $movements->links() }}</div>
                @endif
            @endif
        </div>

        <div x-show="open" x-cloak x-transition.opacity @keydown.escape.window="open = false"
             class="fixed inset-0 z-40 flex items-center justify-center bg-ink-950/50 p-4" role="dialog" aria-modal="true">
            <div @click.outside="open = false" class="w-full max-w-sm rounded-xl bg-white p-5 shadow-xl">
                <h2 class="text-sm font-semibold text-slate-900">Add stock</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Records a restock movement against {{ $product->name }}.
                </p>

                <div class="mt-4 space-y-4">
                    <x-field label="Units" for="restock-quantity" :required="true">
                        <input id="restock-quantity" type="number" min="1" step="1" x-model="quantity"
                               placeholder="0" class="field-input tnum"
                               :class="error && 'field-input-invalid'"
                               @keydown.enter.prevent="confirm()">
                        <template x-if="error">
                            <p class="mt-1.5 text-xs text-rose-600" x-text="error"></p>
                        </template>
                    </x-field>

                    <x-field label="Note" for="restock-note" hint="optional">
                        <input id="restock-note" type="text" x-model="note" maxlength="255"
                               placeholder="Supplier invoice, delivery reference…" class="field-input">
                    </x-field>
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="btn-ghost" @click="open = false">Cancel</button>
                    <button type="button" class="btn-primary" :disabled="working" @click="confirm()">
                        <span x-text="working ? 'Adding…' : 'Add stock'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
