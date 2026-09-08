@extends('layouts.app')

@section('title', 'New Order')

@section('content')
    <script type="application/json" id="catalogue">@json($catalogue)</script>

    <form id="order-form" class="grid grid-cols-1 gap-8 lg:grid-cols-3" novalidate>
        <div class="space-y-8 lg:col-span-2">

            <section>
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Customer</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="customer-email" class="mb-1 block text-xs font-medium text-slate-600">Email</label>
                        <input id="customer-email" name="email" type="email" autocomplete="off" required
                               placeholder="e.g. thomas@example.com"
                               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
                        <p data-error="customer.email" class="mt-1 hidden text-xs text-red-600"></p>
                    </div>
                    <div>
                        <label for="customer-name" class="mb-1 block text-xs font-medium text-slate-600">Name</label>
                        <input id="customer-name" name="name" type="text" autocomplete="off"
                               placeholder="auto-filled if email exists"
                               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
                        <p data-error="customer.name" class="mt-1 hidden text-xs text-red-600"></p>
                    </div>
                </div>
            </section>

            <section>
                <div class="mb-3 flex items-baseline justify-between">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Products</h2>
                    <p data-error="items" class="hidden text-xs text-red-600"></p>
                </div>

                <div class="overflow-hidden rounded-lg border border-slate-300 bg-white shadow-sm">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-200/70 text-left text-xs uppercase tracking-wide text-slate-600">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Product</th>
                                <th class="w-24 px-4 py-3 font-semibold">Qty</th>
                                <th class="w-28 px-4 py-3 text-right font-semibold">Price</th>
                                <th class="w-32 px-4 py-3 text-right font-semibold">Line Total</th>
                                <th class="w-12 px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody id="order-lines" class="divide-y divide-slate-200"></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="px-4 py-3">
                                    <select id="product-picker"
                                            class="w-full rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-500 outline-none focus:border-slate-500">
                                        <option value="">+ choose a product to add a row</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}" @disabled($product->stock_on_hand < 1)>
                                                {{ $product->name }} ({{ $product->code }})
                                                &middot; ₹{{ number_format((float) $product->unit_price, 2) }}
                                                &middot; {{ $product->stock_on_hand }} in stock
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mt-3 flex justify-end">
                    <button type="button" id="add-line"
                            class="rounded-lg bg-slate-700 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-400">
                        + Add Product
                    </button>
                </div>
            </section>

            <section>
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Payment</h2>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div class="rounded-lg border border-slate-300 bg-white p-4 shadow-sm">
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-slate-600">Subtotal</dt>
                                <dd class="font-medium tabular-nums" data-total="subtotal">₹0.00</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-slate-600">Tax</dt>
                                <dd class="font-medium tabular-nums" data-total="tax">₹0.00</dd>
                            </div>
                            <div class="flex justify-between border-t border-slate-200 pt-2">
                                <dt class="font-semibold text-slate-800">Grand Total</dt>
                                <dd class="font-semibold tabular-nums" data-total="grand">₹0.00</dd>
                            </div>
                        </dl>

                        <div class="mt-4 border-t border-dashed border-slate-300 pt-4">
                            <label for="amount-tendered" class="mb-1 block text-xs font-medium text-slate-600">
                                Amount Given by Customer
                            </label>
                            <input id="amount-tendered" type="number" min="0" step="0.01" placeholder="₹0.00"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm tabular-nums shadow-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
                            <p data-error="amount_tendered" class="mt-1 hidden text-xs text-red-600"></p>

                            <div class="mt-3 flex items-baseline justify-between gap-4">
                                <span class="text-sm font-semibold text-slate-800">Balance to Return</span>
                                <span class="text-sm font-semibold tabular-nums" data-total="change">₹0.00</span>
                            </div>
                            <p class="mt-1 text-right text-xs text-slate-500" data-total="change-breakdown"></p>
                        </div>
                    </div>

                    <div class="flex flex-col items-start justify-start gap-3">
                        <button type="submit" id="generate-bill"
                                class="w-full rounded-lg bg-emerald-700 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-300 disabled:cursor-not-allowed disabled:opacity-50">
                            Generate Bill
                        </button>
                        <p class="text-xs leading-relaxed text-slate-500">
                            Saves the order, deducts stock, and queues the confirmation email to the customer.
                        </p>
                        <div id="form-alert" class="hidden w-full rounded-lg border border-red-300 bg-red-50 p-3 text-xs text-red-700"></div>
                    </div>
                </div>
            </section>
        </div>

        <aside class="lg:col-span-1">
            <div class="rounded-lg border-2 border-amber-500 bg-amber-50 p-4">
                <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold text-amber-900">
                    <span aria-hidden="true">&#9888;</span> Low Stock Alert
                </h2>

                @forelse ($lowStock as $product)
                    <p class="border-b border-amber-200/70 py-1.5 text-xs text-amber-900 last:border-0">
                        {{ $product->name }}
                        <span class="text-amber-700">&mdash; {{ $product->stock_on_hand }} units left</span>
                    </p>
                @empty
                    <p class="text-xs text-amber-800">Every product is above its threshold.</p>
                @endforelse

                <p class="mt-4 border-t border-amber-200 pt-3 text-xs leading-relaxed text-amber-800">
                    Threshold falls back to {{ config('inventory.low_stock_threshold') }} units unless a product sets its
                    own. The same list is available at <code class="font-mono">GET /api/products/low-stock</code>.
                </p>
            </div>
        </aside>
    </form>
@endsection
