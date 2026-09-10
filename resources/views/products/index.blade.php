@extends('layouts.app')

@section('title', 'Inventory')

@section('content')
    <div x-data="productForm">

        <x-page-header title="Inventory" description="Catalogue and stock on hand across the counter.">
            <x-slot:actions>
                <button type="button" class="btn-ghost" @click="create()">+ Add product</button>
                <a href="{{ route('billing.index') }}" class="btn-primary">New order</a>
            </x-slot:actions>
        </x-page-header>

        <div class="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <x-stat label="Products" :value="$stats['total']" />
            <x-stat label="Units on hand" :value="number_format($stats['units'])" tone="brand" />
            <x-stat label="Low on stock" :value="$stats['low']" tone="amber" caption="At or below threshold" />
            <x-stat label="Out of stock" :value="$stats['out']" tone="rose" caption="Cannot be sold" />
        </div>

        <form method="GET" action="{{ route('products.index') }}" class="card mb-5 flex flex-wrap items-end gap-3 p-4">
            <x-field label="Search" for="search" class="min-w-64 flex-1">
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
                        </svg>
                    </span>
                    <input id="search" name="search" type="search" value="{{ $search }}" autocomplete="off"
                           placeholder="Name or product code" class="field-input pl-9">
                </div>
            </x-field>

            <label class="flex cursor-pointer items-center gap-2 pb-2.5 text-sm text-slate-600">
                <input type="checkbox" name="low_stock" value="1" @checked($onlyLowStock)
                       class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
                Only low stock
            </label>

            <button type="submit" class="btn-primary">Filter</button>

            @if ($search !== '' || $onlyLowStock)
                <a href="{{ route('products.index') }}" class="btn-ghost">Reset</a>
            @endif
        </form>

        <div class="card overflow-hidden">
            @if ($products->isEmpty())
                <x-empty-state title="No products match"
                               description="Try a different search, or add the product to the catalogue.">
                    <x-slot:icon>
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m21 8-9-5-9 5 9 5 9-5Zm0 8-9 5-9-5m18-4-9 5-9-5"/>
                        </svg>
                    </x-slot:icon>
                    <x-slot:action>
                        <button type="button" class="btn-primary" @click="create()">+ Add product</button>
                    </x-slot:action>
                </x-empty-state>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[56rem] text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs tracking-wide text-slate-500 uppercase">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Product</th>
                                <th class="w-32 px-3 py-3 font-semibold">Code</th>
                                <th class="w-28 px-3 py-3 text-right font-semibold">Price</th>
                                <th class="w-20 px-3 py-3 text-right font-semibold">Tax</th>
                                <th class="w-28 px-3 py-3 text-right font-semibold">Threshold</th>
                                <th class="w-32 px-3 py-3 text-right font-semibold">Stock</th>
                                <th class="w-56 px-5 py-3 text-right font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($products as $product)
                                @php($row = [
                                    'id' => $product->id,
                                    'name' => $product->name,
                                    'code' => $product->code,
                                    'unit_price' => (float) $product->unit_price,
                                    'tax_percentage' => (float) $product->tax_percentage,
                                    'stock_on_hand' => $product->stock_on_hand,
                                    'low_stock_threshold' => $product->low_stock_threshold,
                                ])
                                <tr class="transition hover:bg-slate-50">
                                    <td class="px-5 py-3 font-medium whitespace-nowrap text-slate-800">
                                        <a href="{{ route('products.show', $product) }}" class="hover:text-brand-700 hover:underline">
                                            {{ $product->name }}
                                        </a>
                                    </td>
                                    <td class="px-3 py-3 font-mono text-xs text-slate-400">{{ $product->code }}</td>
                                    <td class="tnum px-3 py-3 text-right text-slate-700">₹{{ number_format((float) $product->unit_price, 2) }}</td>
                                    <td class="tnum px-3 py-3 text-right text-slate-500">{{ rtrim(rtrim(number_format((float) $product->tax_percentage, 2), '0'), '.') }}%</td>
                                    <td class="tnum px-3 py-3 text-right text-slate-500">
                                        {{ $product->effectiveLowStockThreshold() }}
                                        @unless ($product->low_stock_threshold)
                                            <span class="ml-0.5 text-xs text-slate-300" title="Falls back to the application default">def</span>
                                        @endunless
                                    </td>
                                    <td class="px-3 py-3 text-right"><x-stock-badge :product="$product" /></td>
                                    <td class="px-5 py-3">
                                        <div class="flex items-center justify-end gap-1">
                                            <button type="button" class="btn-row btn-row-primary" @click="openRestock(@js($row))">
                                                Restock
                                            </button>
                                            <button type="button" class="btn-row" @click="edit(@js($row))">Edit</button>
                                            <button type="button" class="btn-row btn-row-danger"
                                                    @click="confirmDelete(@js($row))">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <x-pagination :paginator="$products" label="products" />
            @endif
        </div>

        <x-modal title="Product" width="max-w-lg"
                 description="Price and tax are snapshotted onto a bill when it is raised, so changing them here never rewrites history.">
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-field label="Name" for="product-name" :required="true" class="sm:col-span-2">
                    <input id="product-name" x-ref="name" type="text" x-model="form.name" autocomplete="off"
                           placeholder="Amul Milk 1L" class="field-input"
                           :class="errors.name && 'field-input-invalid'">
                    <template x-if="errors.name">
                        <p class="mt-1.5 text-xs text-rose-600" x-text="errors.name"></p>
                    </template>
                </x-field>

                <x-field label="Code" for="product-code" :required="true" hint="unique">
                    <input id="product-code" type="text" x-model="form.code" autocomplete="off"
                           placeholder="GRO-1001" class="field-input font-mono uppercase"
                           :class="errors.code && 'field-input-invalid'">
                    <template x-if="errors.code">
                        <p class="mt-1.5 text-xs text-rose-600" x-text="errors.code"></p>
                    </template>
                </x-field>

                <x-field label="Unit price" for="product-price" :required="true">
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-slate-400">₹</span>
                        <input id="product-price" type="number" min="0" step="0.01" x-model="form.unit_price"
                               placeholder="0.00" class="field-input tnum pl-7"
                               :class="errors.unit_price && 'field-input-invalid'">
                    </div>
                    <template x-if="errors.unit_price">
                        <p class="mt-1.5 text-xs text-rose-600" x-text="errors.unit_price"></p>
                    </template>
                </x-field>

                <x-field label="Tax rate" for="product-tax" :required="true">
                    <div class="relative">
                        <input id="product-tax" type="number" min="0" max="100" step="0.01" x-model="form.tax_percentage"
                               placeholder="18" class="field-input tnum pr-7"
                               :class="errors.tax_percentage && 'field-input-invalid'">
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">%</span>
                    </div>
                    <template x-if="errors.tax_percentage">
                        <p class="mt-1.5 text-xs text-rose-600" x-text="errors.tax_percentage"></p>
                    </template>
                </x-field>

                <x-field label="Low stock threshold" for="product-threshold"
                         hint="optional">
                    <input id="product-threshold" type="number" min="0" step="1" x-model="form.low_stock_threshold"
                           :placeholder="'default ' + {{ config('inventory.low_stock_threshold') }}"
                           class="field-input tnum"
                           :class="errors.low_stock_threshold && 'field-input-invalid'">
                    <template x-if="errors.low_stock_threshold">
                        <p class="mt-1.5 text-xs text-rose-600" x-text="errors.low_stock_threshold"></p>
                    </template>
                </x-field>

                <x-field label="Opening stock" for="product-stock" :required="true" x-show="!editing">
                    <input id="product-stock" type="number" min="0" step="1" x-model="form.stock_on_hand"
                           placeholder="0" class="field-input tnum"
                           :class="errors.stock_on_hand && 'field-input-invalid'">
                    <template x-if="errors.stock_on_hand">
                        <p class="mt-1.5 text-xs text-rose-600" x-text="errors.stock_on_hand"></p>
                    </template>
                </x-field>

                <div class="rounded-lg bg-slate-50 p-3 text-xs text-slate-500 sm:col-span-2" x-show="editing">
                    Stock is not edited here. It moves when a bill is raised, edited or deleted, and when the
                    product is restocked &mdash; every change lands in the product's ledger.
                </div>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" class="btn-ghost" @click="open = false">Cancel</button>
                <button type="button" class="btn-primary" :disabled="working" @click="save()">
                    <span x-text="working ? 'Saving…' : (editing ? 'Save changes' : 'Add product')"></span>
                </button>
            </div>
        </x-modal>

        <x-modal title="Add stock" show="restocking" width="max-w-sm">
            <p class="mt-1 text-sm text-slate-500">
                Records a restock movement against
                <span class="font-medium text-slate-700" x-text="editing?.name"></span>.
            </p>

            <div class="mt-4 space-y-4">
                <x-field label="Units" for="restock-quantity" :required="true">
                    <input id="restock-quantity" x-ref="quantity" type="number" min="1" step="1"
                           x-model="restock.quantity" placeholder="0" class="field-input tnum"
                           :class="errors.quantity && 'field-input-invalid'"
                           @keydown.enter.prevent="addStock()">
                    <template x-if="errors.quantity">
                        <p class="mt-1.5 text-xs text-rose-600" x-text="errors.quantity"></p>
                    </template>
                </x-field>

                <x-field label="Note" for="restock-note" hint="optional">
                    <input id="restock-note" type="text" x-model="restock.note" maxlength="255"
                           placeholder="Supplier invoice, delivery reference&hellip;" class="field-input">
                </x-field>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" class="btn-ghost" @click="restocking = false">Cancel</button>
                <button type="button" class="btn-primary" :disabled="working" @click="addStock()">
                    <span x-text="working ? 'Adding…' : 'Add stock'"></span>
                </button>
            </div>
        </x-modal>

        <x-modal title="Delete this product?" show="confirming" width="max-w-sm">
            <p class="mt-1 text-sm text-slate-500">
                <span class="font-medium text-slate-700" x-text="editing?.name"></span> comes out of the catalogue
                and can no longer be sold. Bills that already carry it keep their lines and their prices.
            </p>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" class="btn-ghost" @click="confirming = false">Keep</button>
                <button type="button" class="btn-danger" :disabled="working" @click="destroy()">
                    <span x-text="working ? 'Deleting…' : 'Delete'"></span>
                </button>
            </div>
        </x-modal>
    </div>
@endsection
