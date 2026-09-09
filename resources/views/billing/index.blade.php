@extends('layouts.app')

@section('title', 'New Order')

@section('content')
    <div x-data="orderForm(@js($catalogue))" x-cloak @keydown.escape="open = false">

        <x-page-header title="New Order" description="Scan or search the catalogue, take payment, and the bill is saved.">
            <x-slot:actions>
                <button type="button" class="btn-ghost" @click="reset()" x-show="lines.length > 0">Clear bill</button>
                <a href="{{ route('orders.index') }}" class="btn-ghost">Recent orders</a>
            </x-slot:actions>
        </x-page-header>

        <form @submit.prevent="submit()" class="grid grid-cols-1 items-start gap-6 xl:grid-cols-3">

            {{-- ------------------------------------------------ left column --}}
            <div class="space-y-6 xl:col-span-2">

                {{-- Customer --}}
                <section class="card p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-900">Customer</h2>
                        <p class="text-xs" x-show="lookup.state !== 'idle'">
                            <span class="text-slate-400" x-show="lookup.state === 'searching'">Looking up&hellip;</span>
                            <span class="badge bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200"
                                  x-show="lookup.state === 'known'">
                                Returning customer &middot; <span x-text="lookup.ordersCount"></span> orders
                            </span>
                            <span class="badge bg-brand-50 text-brand-700 ring-1 ring-brand-200"
                                  x-show="lookup.state === 'new'">New customer</span>
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-field label="Email" for="customer-email" :required="true">
                            <input id="customer-email" type="email" autocomplete="off" inputmode="email"
                                   placeholder="thomas@example.com"
                                   x-model="customer.email"
                                   @input.debounce.400ms="lookupCustomer()"
                                   @blur="lookupCustomer()"
                                   @input="clearError('customer.email')"
                                   :class="errors['customer.email'] && 'field-input-invalid'"
                                   class="field-input">
                            <template x-if="errors['customer.email']">
                                <p class="mt-1.5 text-xs text-rose-600" x-text="errors['customer.email']"></p>
                            </template>
                        </x-field>

                        <x-field label="Name" for="customer-name">
                            <input id="customer-name" type="text" autocomplete="off"
                                   placeholder="Filled in automatically for a returning customer"
                                   x-model="customer.name"
                                   @input="clearError('customer.name')"
                                   :class="errors['customer.name'] && 'field-input-invalid'"
                                   class="field-input">
                            <template x-if="errors['customer.name']">
                                <p class="mt-1.5 text-xs text-rose-600" x-text="errors['customer.name']"></p>
                            </template>
                        </x-field>
                    </div>
                </section>

                {{-- Products --}}
                <section class="card overflow-hidden">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <h2 class="text-sm font-semibold text-slate-900">Items</h2>
                        <p class="text-xs text-slate-500">
                            <span class="tnum font-medium text-slate-700" x-text="lines.length"></span> lines
                            &middot;
                            <span class="tnum font-medium text-slate-700" x-text="itemCount"></span> units
                        </p>
                    </div>

                    {{-- Searchable product picker --}}
                    <div class="border-b border-slate-200 bg-slate-50/70 p-4" @click.outside="open = false">
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                    <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
                                </svg>
                            </span>

                            <input type="text" x-ref="search" x-model="search" role="combobox" aria-expanded="false"
                                   :aria-expanded="open.toString()" aria-controls="product-options" autocomplete="off"
                                   placeholder="Search the catalogue by name or code&hellip;"
                                   @focus="openPicker()"
                                   @input="openPicker()"
                                   @keydown.arrow-down.prevent="move(1)"
                                   @keydown.arrow-up.prevent="move(-1)"
                                   @keydown.enter.prevent="chooseHighlighted()"
                                   class="field-input pl-9">

                            <ul id="product-options" role="listbox" x-show="open && matches.length > 0"
                                x-transition.opacity.duration.120ms
                                class="absolute inset-x-0 top-full z-20 mt-1.5 max-h-72 overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
                                <template x-for="(product, index) in matches" :key="product.id">
                                    <li role="option" :aria-selected="(index === highlighted).toString()"
                                        @mouseenter="highlighted = index"
                                        @click="choose(product)"
                                        class="flex cursor-pointer items-center gap-3 px-3 py-2 text-sm"
                                        :class="index === highlighted ? 'bg-brand-50' : ''">
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate font-medium text-slate-800" x-text="product.name"></span>
                                            <span class="block font-mono text-xs text-slate-400" x-text="product.code"></span>
                                        </span>
                                        <span class="tnum shrink-0 text-sm text-slate-600" x-text="money(product.unit_price * 100)"></span>
                                        <span class="badge shrink-0"
                                              :class="product.stock_on_hand === 0
                                                  ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-200'
                                                  : (product.stock_on_hand <= 10
                                                      ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'
                                                      : 'bg-slate-100 text-slate-600')"
                                              x-text="product.stock_on_hand + ' left'"></span>
                                    </li>
                                </template>
                            </ul>

                            <p x-show="open && matches.length === 0"
                               class="absolute inset-x-0 top-full z-20 mt-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-500 shadow-lg">
                                Nothing in the catalogue matches &ldquo;<span class="font-medium" x-text="search"></span>&rdquo;.
                            </p>
                        </div>

                        <p class="mt-2 text-xs text-slate-400">
                            Type to filter, arrow keys to move, Enter to add.
                        </p>
                    </div>

                    {{-- Lines --}}
                    <template x-if="lines.length === 0">
                        <div class="px-6 py-14 text-center">
                            <p class="text-sm font-semibold text-slate-600">No items on this bill yet</p>
                            <p class="mt-1 text-sm text-slate-400">Search above to add the first one.</p>
                        </div>
                    </template>

                    <div class="overflow-x-auto" x-show="lines.length > 0">
                    <table class="w-full min-w-[40rem] text-sm">
                        <thead class="border-b border-slate-200 text-left text-xs tracking-wide text-slate-500 uppercase">
                            <tr>
                                <th class="min-w-52 px-5 py-2.5 font-semibold">Product</th>
                                <th class="w-28 px-3 py-2.5 font-semibold">Qty</th>
                                <th class="w-28 px-3 py-2.5 text-right font-semibold">Rate</th>
                                <th class="w-24 px-3 py-2.5 text-right font-semibold">Tax</th>
                                <th class="w-32 px-3 py-2.5 text-right font-semibold">Total</th>
                                <th class="w-12 px-3 py-2.5"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(line, index) in lines" :key="line.product.id">
                                <tr :class="shortages.some(s => s.product_id === line.product.id) && 'bg-rose-50'">
                                    <td class="min-w-52 px-5 py-3">
                                        <span class="block font-medium text-slate-800" x-text="line.product.name"></span>
                                        <span class="block font-mono text-xs text-slate-400" x-text="line.product.code"></span>
                                        <template x-if="shortages.find(s => s.product_id === line.product.id)">
                                            <span class="mt-1 block text-xs font-medium text-rose-600"
                                                  x-text="'Only ' + shortages.find(s => s.product_id === line.product.id).available + ' left on the shelf'"></span>
                                        </template>
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="flex items-center rounded-lg border border-slate-300 bg-white">
                                            <button type="button" aria-label="Decrease"
                                                    @click="setQuantity(line, line.quantity - 1)"
                                                    class="px-2 py-1.5 text-slate-400 transition hover:text-slate-700">&minus;</button>
                                            <input type="text" inputmode="numeric" :value="line.quantity"
                                                   @input="setQuantity(line, $event.target.value)"
                                                   @blur="$event.target.value = line.quantity"
                                                   class="tnum w-9 border-0 bg-transparent p-0 text-center text-sm focus:ring-0 focus:outline-none">
                                            <button type="button" aria-label="Increase"
                                                    @click="setQuantity(line, line.quantity + 1)"
                                                    :disabled="line.quantity >= line.product.stock_on_hand"
                                                    class="px-2 py-1.5 text-slate-400 transition hover:text-slate-700 disabled:opacity-30">+</button>
                                        </div>
                                    </td>
                                    <td class="tnum px-3 py-3 text-right text-slate-600" x-text="money(line.product.unit_price * 100)"></td>
                                    <td class="tnum px-3 py-3 text-right text-xs text-slate-400" x-text="line.product.tax_percentage + '%'"></td>
                                    <td class="tnum px-3 py-3 text-right font-semibold text-slate-800" x-text="money(lineTotal(line))"></td>
                                    <td class="px-3 py-3 text-right">
                                        <button type="button" @click="remove(index)" aria-label="Remove line"
                                                class="rounded p-1 text-slate-300 transition hover:bg-rose-50 hover:text-rose-600">
                                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                 stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                                <path d="M6 6l12 12M18 6 6 18"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    </div>

                    <template x-if="errors.items">
                        <p class="border-t border-rose-100 bg-rose-50 px-5 py-2.5 text-xs font-medium text-rose-700"
                           x-text="errors.items"></p>
                    </template>
                </section>
            </div>

            {{-- ----------------------------------------------- right column --}}
            <div class="space-y-6 xl:sticky xl:top-24">

                {{-- Payment --}}
                <section class="card p-5">
                    <h2 class="mb-4 text-sm font-semibold text-slate-900">Payment</h2>

                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Subtotal</dt>
                            <dd class="tnum font-medium text-slate-700" x-text="money(subtotal)"></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Tax</dt>
                            <dd class="tnum font-medium text-slate-700" x-text="money(tax)"></dd>
                        </div>
                        <div class="flex items-baseline justify-between border-t border-slate-200 pt-2.5">
                            <dt class="font-semibold text-slate-900">Grand Total</dt>
                            <dd class="tnum text-xl font-semibold text-slate-900" x-text="money(grandTotal)"></dd>
                        </div>
                    </dl>

                    <div class="mt-5 border-t border-dashed border-slate-200 pt-4">
                        <x-field label="Amount given by customer" for="tendered" hint="optional">
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-slate-400">₹</span>
                                <input id="tendered" type="number" min="0" step="0.01" placeholder="0.00"
                                       x-model="tendered" @input="clearError('amount_tendered')"
                                       :class="(errors.amount_tendered || isShort) && 'field-input-invalid'"
                                       class="field-input tnum pl-7">
                            </div>
                            <template x-if="errors.amount_tendered">
                                <p class="mt-1.5 text-xs text-rose-600" x-text="errors.amount_tendered"></p>
                            </template>
                        </x-field>

                        <div class="mt-4 rounded-lg bg-slate-50 p-3" x-show="tendered !== ''" x-transition.opacity>
                            <div class="flex items-baseline justify-between">
                                <span class="text-sm font-semibold text-slate-700">Balance to return</span>
                                <span class="tnum text-lg font-semibold"
                                      :class="isShort ? 'text-rose-600' : 'text-emerald-600'"
                                      x-text="isShort ? money(0) : money(changeDue)"></span>
                            </div>
                            <div class="mt-2 flex flex-wrap gap-1.5" x-show="changeParts.length > 0">
                                <template x-for="part in changeParts" :key="part.denomination">
                                    <span class="badge bg-white text-slate-600 ring-1 ring-slate-200 tnum"
                                          x-text="part.count + ' × ₹' + part.denomination"></span>
                                </template>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-success mt-5 w-full py-3"
                            :disabled="submitting || lines.length === 0">
                        <svg x-show="submitting" class="size-4 animate-spin" viewBox="0 0 24 24" fill="none"
                             aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"/>
                        </svg>
                        <span x-text="submitting ? 'Saving bill…' : 'Generate Bill'"></span>
                    </button>

                    <p class="mt-2.5 text-center text-xs text-slate-400">
                        Deducts stock and queues the confirmation email.
                    </p>
                </section>

                {{-- Low stock --}}
                <section class="card overflow-hidden">
                    <div class="flex items-center gap-2 border-b border-amber-200 bg-amber-50 px-4 py-3">
                        <svg class="size-4 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <path d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/>
                        </svg>
                        <h2 class="text-sm font-semibold text-amber-900">Low stock</h2>
                        <span class="badge ml-auto bg-amber-100 text-amber-800">{{ $lowStock->count() }}</span>
                    </div>

                    @forelse ($lowStock->take(6) as $product)
                        <div class="flex items-center gap-3 border-b border-slate-100 px-4 py-2.5 last:border-0">
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm text-slate-700">{{ $product->name }}</span>
                                <span class="block font-mono text-xs text-slate-400">{{ $product->code }}</span>
                            </span>
                            <x-stock-badge :product="$product" />
                        </div>
                    @empty
                        <p class="px-4 py-6 text-center text-sm text-slate-400">Everything is above its threshold.</p>
                    @endforelse

                    <a href="{{ route('products.index', ['low_stock' => 1]) }}"
                       class="block border-t border-slate-100 px-4 py-2.5 text-xs font-medium text-brand-700 transition hover:bg-slate-50">
                        View all in inventory &rarr;
                    </a>
                </section>
            </div>
        </form>
    </div>
@endsection
