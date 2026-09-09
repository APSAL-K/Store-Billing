@extends('layouts.app')

@section('title', $order ? 'Edit ' . $order->reference : 'New Order')

@section('content')
    @php
        $config = [
            'catalogue' => $catalogue,
            'customers' => $customers,
            'orderId' => $order?->id,
            'customerId' => $order?->customer_id,
            'lines' => $existingLines,
            'tendered' => $order?->amount_tendered !== null ? (float) $order->amount_tendered : '',
        ];
    @endphp

    <div x-data="orderForm(@js($config))" x-cloak @keydown.escape="customerOpen = false">

        <x-page-header
            :title="$order ? 'Edit bill ' . $order->reference : 'New Order'"
            :description="$order
                ? 'Changing the lines returns the difference to stock and reprices the bill.'
                : 'Pick a customer, choose items, take payment.'">
            <x-slot:actions>
                @if ($order)
                    <a href="{{ route('orders.show', $order) }}" class="btn-ghost">Cancel</a>
                @else
                    <button type="button" class="btn-ghost" @click="reset()"
                            x-show="lines.length > 0 || customerId !== null">Clear bill</button>
                    <a href="{{ route('orders.index') }}" class="btn-ghost">Recent orders</a>
                @endif
            </x-slot:actions>
        </x-page-header>

        <form @submit.prevent="submit()" novalidate class="grid grid-cols-1 items-start gap-6 xl:grid-cols-4">

            <div class="space-y-6 xl:col-span-3">

                <section class="card p-5">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                            <span class="flex size-5 items-center justify-center rounded-full bg-brand-100 text-[11px] font-bold text-brand-700">1</span>
                            Customer
                        </h2>
                        <button type="button" class="text-xs font-semibold text-brand-700 hover:underline"
                                x-show="mode === 'existing'" @click="startNewCustomer()">
                            + New customer
                        </button>
                        <button type="button" class="text-xs font-semibold text-slate-500 hover:underline"
                                x-show="mode === 'new'" @click="cancelNewCustomer()">
                            Choose an existing customer instead
                        </button>
                    </div>

                    <div x-show="mode === 'existing'">
                        <div x-show="selectedCustomer"
                             class="flex items-center gap-3 rounded-lg border border-brand-200 bg-brand-50 p-3">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-600 text-sm font-semibold text-white"
                                  x-text="selectedCustomer ? selectedCustomer.name.charAt(0).toUpperCase() : ''"></span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-slate-900" x-text="selectedCustomer?.name"></span>
                                <span class="block truncate text-xs text-slate-500" x-text="selectedCustomer?.email"></span>
                            </span>
                            <span class="badge shrink-0 bg-white text-slate-600 ring-1 ring-slate-200"
                                  x-text="(selectedCustomer?.orders_count ?? 0) + ' bills'"></span>
                            <button type="button" @click="clearCustomer()"
                                    class="shrink-0 rounded p-1 text-slate-400 transition hover:bg-white hover:text-slate-700"
                                    aria-label="Change customer">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                    <path d="M6 6l12 12M18 6 6 18"/>
                                </svg>
                            </button>
                        </div>

                        <div x-show="!selectedCustomer" class="relative" @click.outside="customerOpen = false">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                                </svg>
                            </span>

                            <input type="text" x-ref="customerSearch" x-model="customerSearch" role="combobox"
                                   :aria-expanded="customerOpen.toString()" autocomplete="off"
                                   placeholder="Search customers by name or email&hellip;"
                                   @focus="openCustomers()" @input="openCustomers()"
                                   @keydown.arrow-down.prevent="moveCustomer(1)"
                                   @keydown.arrow-up.prevent="moveCustomer(-1)"
                                   @keydown.enter.prevent="pickHighlightedCustomer()"
                                   :class="errors['customer.email'] && 'field-input-invalid'"
                                   class="field-input pl-9">

                            <ul x-show="customerOpen" x-transition.opacity.duration.120ms role="listbox"
                                class="absolute inset-x-0 top-full z-20 mt-1.5 max-h-72 overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
                                <template x-for="(customer, index) in customerMatches" :key="customer.id">
                                    <li role="option" :aria-selected="(index === customerHighlighted).toString()"
                                        @mouseenter="customerHighlighted = index" @click="pickCustomer(customer)"
                                        class="flex cursor-pointer items-center gap-3 px-3 py-2 text-sm"
                                        :class="index === customerHighlighted ? 'bg-brand-50' : ''">
                                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-slate-200 text-xs font-semibold text-slate-600"
                                              x-text="customer.name.charAt(0).toUpperCase()"></span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate font-medium text-slate-800" x-text="customer.name"></span>
                                            <span class="block truncate text-xs text-slate-400" x-text="customer.email"></span>
                                        </span>
                                        <span class="tnum shrink-0 text-xs text-slate-400"
                                              x-text="customer.orders_count + ' bills'"></span>
                                    </li>
                                </template>

                                <li x-show="customerMatches.length === 0"
                                    class="px-3 py-2.5 text-sm text-slate-500">
                                    No customer matches &ldquo;<span class="font-medium" x-text="customerSearch"></span>&rdquo;.
                                </li>

                                <li class="mt-1 border-t border-slate-100 pt-1">
                                    <button type="button" @click="startNewCustomer()"
                                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm font-medium text-brand-700 hover:bg-brand-50">
                                        <span class="flex size-7 items-center justify-center rounded-full bg-brand-100 text-brand-700">+</span>
                                        Add a new customer
                                    </button>
                                </li>
                            </ul>

                            <template x-if="errors['customer.email'] && mode === 'existing'">
                                <p class="mt-1.5 text-xs text-rose-600" x-text="errors['customer.email']"></p>
                            </template>
                        </div>
                    </div>

                    <div x-show="mode === 'new'" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-field label="Name" for="draft-name" :required="true">
                            <input id="draft-name" x-ref="draftName" type="text" autocomplete="off"
                                   placeholder="Priya Nair" x-model="draft.name"
                                   @input="clearError('customer.name')"
                                   :class="errors['customer.name'] && 'field-input-invalid'"
                                   class="field-input">
                            <template x-if="errors['customer.name']">
                                <p class="mt-1.5 text-xs text-rose-600" x-text="errors['customer.name']"></p>
                            </template>
                        </x-field>

                        <x-field label="Email" for="draft-email" :required="true">
                            <input id="draft-email" type="email" inputmode="email" autocomplete="off"
                                   placeholder="priya@example.com" x-model="draft.email"
                                   @input="clearError('customer.email')"
                                   :class="errors['customer.email'] && 'field-input-invalid'"
                                   class="field-input">
                            <template x-if="errors['customer.email']">
                                <p class="mt-1.5 text-xs text-rose-600" x-text="errors['customer.email']"></p>
                            </template>
                        </x-field>
                    </div>
                </section>

                <section class="card overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                        <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                            <span class="flex size-5 items-center justify-center rounded-full bg-brand-100 text-[11px] font-bold text-brand-700">2</span>
                            Items
                        </h2>
                        <div class="relative w-full sm:w-72">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                    <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
                                </svg>
                            </span>
                            <input type="search" x-model="search" autocomplete="off"
                                   placeholder="Search the catalogue&hellip;" class="field-input py-1.5 pl-9 text-sm">
                        </div>
                    </div>

                    <div class="max-h-96 overflow-y-auto bg-slate-50/60 p-4">
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                            <template x-for="product in visibleProducts" :key="product.id">
                                <button type="button" @click="add(product)"
                                        :disabled="product.stock_on_hand < 1"
                                        class="group relative flex flex-col rounded-xl border bg-white p-3 text-left transition
                                               disabled:cursor-not-allowed disabled:opacity-50"
                                        :class="quantityOf(product) > 0
                                            ? 'border-brand-500 ring-2 ring-brand-500/20'
                                            : 'border-slate-200 hover:border-brand-300 hover:shadow-sm'">
                                    <span x-show="quantityOf(product) > 0"
                                          class="tnum absolute -top-2 -right-2 flex size-6 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white shadow"
                                          x-text="quantityOf(product)"></span>

                                    <span class="line-clamp-2 min-h-9 text-sm leading-tight font-medium text-slate-800"
                                          x-text="product.name"></span>
                                    <span class="mt-1 font-mono text-[11px] text-slate-400" x-text="product.code"></span>

                                    <span class="mt-2 flex items-center justify-between gap-2">
                                        <span class="tnum text-sm font-semibold text-slate-900"
                                              x-text="money(product.unit_price * 100)"></span>
                                        <span class="badge text-[11px]"
                                              :class="product.stock_on_hand === 0
                                                  ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-200'
                                                  : (product.stock_on_hand <= 10
                                                      ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'
                                                      : 'bg-slate-100 text-slate-500')"
                                              x-text="product.stock_on_hand === 0 ? 'Out' : product.stock_on_hand + ' left'"></span>
                                    </span>
                                </button>
                            </template>
                        </div>

                        <p x-show="visibleProducts.length === 0" class="py-10 text-center text-sm text-slate-400">
                            Nothing in the catalogue matches &ldquo;<span class="font-medium" x-text="search"></span>&rdquo;.
                        </p>
                    </div>

                    <div class="border-t border-slate-200">
                        <div class="flex items-center justify-between px-5 py-3">
                            <h3 class="text-xs font-semibold tracking-wide text-slate-500 uppercase">On this bill</h3>
                            <p class="text-xs text-slate-500">
                                <span class="tnum font-medium text-slate-700" x-text="lines.length"></span> lines
                                &middot;
                                <span class="tnum font-medium text-slate-700" x-text="itemCount"></span> units
                            </p>
                        </div>

                        <p x-show="lines.length === 0" class="px-5 pb-6 text-center text-sm text-slate-400">
                            Tap a product above to put it on the bill.
                        </p>

                        <div class="overflow-x-auto" x-show="lines.length > 0">
                            <table class="w-full min-w-[38rem] text-sm">
                                <thead class="border-y border-slate-200 bg-slate-50 text-left text-xs tracking-wide text-slate-500 uppercase">
                                    <tr>
                                        <th class="min-w-48 px-5 py-2.5 font-semibold">Product</th>
                                        <th class="w-32 px-3 py-2.5 font-semibold">Qty</th>
                                        <th class="w-24 px-3 py-2.5 text-right font-semibold">Rate</th>
                                        <th class="w-20 px-3 py-2.5 text-right font-semibold">Tax</th>
                                        <th class="w-28 px-3 py-2.5 text-right font-semibold">Total</th>
                                        <th class="w-12 px-3 py-2.5"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="(line, index) in lines" :key="line.product.id">
                                        <tr :class="shortageFor(line.product.id) && 'bg-rose-50'">
                                            <td class="px-5 py-2.5">
                                                <span class="block font-medium text-slate-800" x-text="line.product.name"></span>
                                                <template x-if="shortageFor(line.product.id)">
                                                    <span class="block text-xs font-medium text-rose-600"
                                                          x-text="'Only ' + shortageFor(line.product.id).available + ' left on the shelf'"></span>
                                                </template>
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <div class="inline-flex items-center rounded-lg border border-slate-300 bg-white">
                                                    <button type="button" aria-label="Decrease"
                                                            @click="setQuantity(line, line.quantity - 1)"
                                                            class="px-2 py-1 text-slate-400 transition hover:text-slate-700">&minus;</button>
                                                    <input type="text" inputmode="numeric" :value="line.quantity"
                                                           @input="setQuantity(line, $event.target.value)"
                                                           @blur="$event.target.value = line.quantity"
                                                           class="tnum w-9 border-0 bg-transparent p-0 text-center text-sm focus:ring-0 focus:outline-none">
                                                    <button type="button" aria-label="Increase"
                                                            @click="setQuantity(line, line.quantity + 1)"
                                                            :disabled="line.quantity >= line.product.stock_on_hand"
                                                            class="px-2 py-1 text-slate-400 transition hover:text-slate-700 disabled:opacity-30">+</button>
                                                </div>
                                            </td>
                                            <td class="tnum px-3 py-2.5 text-right text-slate-600"
                                                x-text="money(line.product.unit_price * 100)"></td>
                                            <td class="tnum px-3 py-2.5 text-right text-xs text-slate-400"
                                                x-text="line.product.tax_percentage + '%'"></td>
                                            <td class="tnum px-3 py-2.5 text-right font-semibold text-slate-800"
                                                x-text="money(lineTotal(line))"></td>
                                            <td class="px-3 py-2.5 text-right">
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
                    </div>
                </section>

                <section class="card p-5">
                    <h2 class="mb-4 flex items-center gap-2 text-sm font-semibold text-slate-900">
                        <span class="flex size-5 items-center justify-center rounded-full bg-brand-100 text-[11px] font-bold text-brand-700">3</span>
                        Payment
                    </h2>

                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <div>
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
                                    <dd class="tnum text-2xl font-semibold text-slate-900" x-text="money(grandTotal)"></dd>
                                </div>
                            </dl>

                            <button type="submit" class="btn-success mt-5 w-full py-3"
                                    :disabled="submitting || lines.length === 0">
                                <svg x-show="submitting" class="size-4 animate-spin" viewBox="0 0 24 24" fill="none"
                                     aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"/>
                                </svg>
                                <span x-text="submitting
                                    ? (isEditing ? 'Saving changes…' : 'Saving bill…')
                                    : (isEditing ? 'Save changes' : 'Generate Bill')"></span>
                            </button>

                            <p class="mt-2.5 text-center text-xs text-slate-400">
                                <span x-show="!isEditing">Deducts stock and queues the confirmation email.</span>
                                <span x-show="isEditing">Reconciles stock against the previous lines.</span>
                            </p>
                        </div>

                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
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

                            <div class="mt-2 flex flex-wrap gap-1.5" x-show="suggestedTenders.length > 0">
                                <template x-for="amount in suggestedTenders" :key="amount">
                                    <button type="button" @click="quickTender(amount)"
                                            class="tnum rounded-md border border-slate-300 bg-white px-2 py-1 text-xs font-medium text-slate-600 transition hover:border-brand-400 hover:text-brand-700"
                                            x-text="'₹' + amount.toLocaleString('en-IN')"></button>
                                </template>
                            </div>

                            <div class="mt-4 border-t border-dashed border-slate-300 pt-3" x-show="tendered !== ''">
                                <div class="flex items-baseline justify-between">
                                    <span class="text-sm font-semibold text-slate-700">Balance to return</span>
                                    <span class="tnum text-lg font-semibold"
                                          :class="isShort ? 'text-rose-600' : 'text-emerald-600'"
                                          x-text="isShort ? money(0) : money(changeDue)"></span>
                                </div>
                                <div class="mt-2 flex flex-wrap gap-1.5" x-show="changeParts.length > 0">
                                    <template x-for="part in changeParts" :key="part.denomination">
                                        <span class="badge tnum bg-white text-slate-600 ring-1 ring-slate-200"
                                              x-text="part.count + ' × ₹' + part.denomination"></span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="xl:sticky xl:top-24 xl:col-span-1">
                <section class="card overflow-hidden">
                    <div class="flex items-center gap-2 border-b border-amber-200 bg-amber-50 px-4 py-3">
                        <svg class="size-4 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <path d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/>
                        </svg>
                        <h2 class="text-sm font-semibold text-amber-900">Low stock</h2>
                        <span class="badge ml-auto bg-amber-100 text-amber-800">{{ $lowStock->count() }}</span>
                    </div>

                    @forelse ($lowStock->take(8) as $product)
                        <a href="{{ route('products.show', $product) }}"
                           class="flex items-center gap-3 border-b border-slate-100 px-4 py-2.5 transition last:border-0 hover:bg-slate-50">
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm text-slate-700">{{ $product->name }}</span>
                                <span class="block font-mono text-xs text-slate-400">{{ $product->code }}</span>
                            </span>
                            <x-stock-badge :product="$product" />
                        </a>
                    @empty
                        <p class="px-4 py-8 text-center text-sm text-slate-400">Everything is above its threshold.</p>
                    @endforelse

                    <a href="{{ route('products.index', ['low_stock' => 1]) }}"
                       class="block border-t border-slate-100 px-4 py-2.5 text-xs font-medium text-brand-700 transition hover:bg-slate-50">
                        View all in inventory &rarr;
                    </a>
                </section>
            </aside>
        </form>
    </div>
@endsection
