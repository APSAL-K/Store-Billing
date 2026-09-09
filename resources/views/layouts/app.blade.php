@php
    $tabs = [
        ['route' => 'billing.index', 'label' => 'New Order', 'active' => request()->routeIs('billing.index')],
        ['route' => 'orders.index', 'label' => 'Orders', 'active' => request()->routeIs('orders.*')],
        ['route' => 'products.index', 'label' => 'Inventory', 'active' => request()->routeIs('products.*')],
    ];
@endphp

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Counter') &middot; {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col">

    <header class="no-print sticky top-0 z-30 bg-ink-950 text-slate-300">
        <div class="mx-auto flex max-w-7xl items-center gap-6 px-4 sm:px-6">
            <a href="{{ route('billing.index') }}" class="flex shrink-0 items-center gap-2.5 py-3.5">
                <span class="flex size-8 items-center justify-center rounded-lg bg-brand-600 text-white">
                    <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 3h2l.4 2M7 13h10l3-8H5.4M7 13 5.4 5M7 13l-2 5h13"/>
                        <circle cx="9" cy="20" r="1"/><circle cx="17" cy="20" r="1"/>
                    </svg>
                </span>
                <span class="text-[15px] font-semibold tracking-tight text-white">{{ config('app.name') }}</span>
            </a>

            <nav class="-mb-px flex items-center gap-1 overflow-x-auto" aria-label="Sections">
                @foreach ($tabs as $tab)
                    <a href="{{ route($tab['route']) }}"
                       @if ($tab['active']) aria-current="page" @endif
                       class="relative border-b-2 px-3 py-4 text-sm font-medium whitespace-nowrap transition
                              {{ $tab['active']
                                  ? 'border-brand-500 text-white'
                                  : 'border-transparent text-slate-400 hover:border-slate-600 hover:text-slate-200' }}">
                        {{ $tab['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="ml-auto hidden items-center gap-3 text-xs text-slate-400 sm:flex">
                <span class="flex items-center gap-1.5">
                    <span class="size-1.5 rounded-full bg-emerald-400"></span>
                    Counter 1
                </span>
                <span class="tnum">{{ now()->format('d M Y') }}</span>
            </div>
        </div>
    </header>

    <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-7 sm:px-6">
        @yield('content')
    </main>

    <footer class="no-print border-t border-slate-200 py-5">
        <div class="mx-auto max-w-7xl px-4 text-xs text-slate-400 sm:px-6">
            Store Order &amp; Inventory Mini-System &middot; Laravel {{ app()->version() }}
        </div>
    </footer>

    {{-- Toasts are rendered by the Alpine store so any page can raise one. --}}
    <div x-data x-cloak
         class="no-print pointer-events-none fixed inset-x-0 bottom-0 z-50 flex flex-col items-center gap-2 p-4 sm:items-end">
        <template x-for="toast in $store.toasts.items" :key="toast.id">
            <div x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="translate-y-2 opacity-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-end="opacity-0"
                 class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border bg-white p-3.5 shadow-lg"
                 :class="toast.tone === 'error'
                     ? 'border-rose-200'
                     : (toast.tone === 'success' ? 'border-emerald-200' : 'border-slate-200')">
                <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full text-white"
                      :class="toast.tone === 'error'
                          ? 'bg-rose-500'
                          : (toast.tone === 'success' ? 'bg-emerald-500' : 'bg-slate-400')">
                    <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <template x-if="toast.tone === 'success'"><path d="m5 13 4 4L19 7"/></template>
                        <template x-if="toast.tone !== 'success'"><path d="M12 8v5m0 3h.01"/></template>
                    </svg>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-slate-800" x-text="toast.title"></p>
                    <p class="mt-0.5 text-sm text-slate-500" x-text="toast.body" x-show="toast.body"></p>
                </div>
                <button type="button" @click="$store.toasts.dismiss(toast.id)"
                        class="-m-1 shrink-0 rounded p-1 text-slate-400 transition hover:text-slate-600"
                        aria-label="Dismiss">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                         stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
            </div>
        </template>
    </div>
</body>
</html>
