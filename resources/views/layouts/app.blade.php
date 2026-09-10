@php
    $tabs = [
        ['route' => 'dashboard', 'label' => 'Dashboard', 'active' => request()->routeIs('dashboard'),
         'icon' => 'M3 13h8V3H3v10Zm10 8h8V11h-8v10ZM3 21h8v-6H3v6Zm10-12h8V3h-8v6Z'],
        ['route' => 'billing.index', 'label' => 'New Order', 'active' => request()->routeIs('billing.*'),
         'icon' => 'M12 5v14m-7-7h14'],
        ['route' => 'orders.index', 'label' => 'Orders', 'active' => request()->routeIs('orders.*'),
         'icon' => 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M8 13h8M8 17h5'],
        ['route' => 'customers.index', 'label' => 'Customers', 'active' => request()->routeIs('customers.*'),
         'icon' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM22 21v-2a4 4 0 0 0-3-3.87'],
        ['route' => 'products.index', 'label' => 'Inventory', 'active' => request()->routeIs('products.*'),
         'icon' => 'm21 8-9-5-9 5 9 5 9-5Zm0 8-9 5-9-5m18-4-9 5-9-5'],
    ];

    $mark = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">'
        .'<rect width="32" height="32" rx="7" fill="#6d3fe0"/>'
        .'<path d="M8 9h2l.6 2.6M12 20h9l2.6-8H10.6M12 20l-2-8m2 8-1.6 4.4h11" fill="none" stroke="#fff" '
        .'stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
@endphp

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Store Order &amp; Inventory Mini-System">
    <meta name="theme-color" content="#f7f6f4">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>@yield('title', 'Counter') &middot; {{ config('app.name') }}</title>

    <link rel="icon" href="data:image/svg+xml,{{ rawurlencode($mark) }}">
    <link rel="apple-touch-icon" href="data:image/svg+xml,{{ rawurlencode($mark) }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col">

    <a href="#main"
       class="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-50 focus:rounded-lg focus:bg-brand-600 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">
        Skip to content
    </a>

    <header x-data="{ menu: false }" class="no-print sticky top-0 z-30 bg-shell text-shell-ink shadow-sm">
        <div class="mx-auto flex max-w-7xl items-center gap-3 px-4 sm:px-6">

            <a href="{{ route('dashboard') }}"
               class="flex shrink-0 items-center gap-2.5 py-3.5 transition hover:opacity-80">
                <span class="flex size-8 items-center justify-center rounded-lg bg-brand-600 text-white shadow-sm shadow-brand-600/40">
                    <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 3h2l.4 2M7 13h10l3-8H5.4M7 13 5.4 5M7 13l-2 5h13"/>
                        <circle cx="9" cy="20" r="1"/><circle cx="17" cy="20" r="1"/>
                    </svg>
                </span>
                <span class="text-[15px] font-semibold tracking-tight whitespace-nowrap">{{ config('app.name') }}</span>
            </a>

            <nav class="-mb-px hidden items-center gap-0.5 md:flex" aria-label="Sections">
                @foreach ($tabs as $tab)
                    <a href="{{ route($tab['route']) }}"
                       @if ($tab['active']) aria-current="page" @endif
                       class="relative border-b-2 px-3 py-4 text-sm font-medium whitespace-nowrap transition
                              {{ $tab['active']
                                  ? 'border-brand-500 text-shell-ink'
                                  : 'border-transparent text-shell-ink/55 hover:border-shell-line hover:text-shell-ink/90' }}">
                        {{ $tab['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="ml-auto flex items-center gap-1.5 sm:gap-2.5">
                <span class="hidden items-center gap-3 text-xs text-shell-ink/55 lg:flex">
                    <span class="flex items-center gap-1.5">
                        <span class="size-1.5 rounded-full bg-success"></span>
                        Counter 1
                    </span>
                    <span class="tnum">{{ now()->format('d M Y') }}</span>
                </span>

                <a href="{{ route('billing.index') }}"
                   class="hidden rounded-lg bg-brand-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-500 sm:inline-block">
                    New order
                </a>

                <button type="button" @click="menu = ! menu"
                        class="flex size-8 items-center justify-center rounded-lg text-shell-ink/60 transition hover:bg-white/10 hover:text-shell-ink md:hidden"
                        :aria-expanded="menu.toString()" aria-label="Menu">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" aria-hidden="true">
                        <path x-show="! menu" d="M4 7h16M4 12h16M4 17h16"/>
                        <path x-show="menu" x-cloak d="M6 6l12 12M18 6 6 18"/>
                    </svg>
                </button>
            </div>
        </div>

        <nav x-show="menu" x-cloak x-collapse @click="menu = false"
             class="border-t border-shell-line md:hidden" aria-label="Sections">
            @foreach ($tabs as $tab)
                <a href="{{ route($tab['route']) }}"
                   @if ($tab['active']) aria-current="page" @endif
                   class="flex items-center gap-3 border-l-2 px-5 py-3 text-sm font-medium transition
                          {{ $tab['active']
                              ? 'border-brand-500 bg-white/5 text-shell-ink'
                              : 'border-transparent text-shell-ink/60 hover:bg-white/5' }}">
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="{{ $tab['icon'] }}"/>
                    </svg>
                    {{ $tab['label'] }}
                </a>
            @endforeach
        </nav>
    </header>

    <main id="main" class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6 sm:py-7">
        @yield('content')
    </main>

    <footer class="no-print mt-8 border-t border-line bg-surface">
        <div class="mx-auto flex max-w-7xl flex-col gap-5 px-4 py-6 sm:px-6 md:flex-row md:items-center md:justify-between">
            <div class="flex items-start gap-3">
                <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-white">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 3h2l.4 2M7 13h10l3-8H5.4M7 13 5.4 5M7 13l-2 5h13"/>
                        <circle cx="9" cy="20" r="1"/><circle cx="17" cy="20" r="1"/>
                    </svg>
                </span>
                <div>
                    <p class="text-sm font-semibold text-ink">{{ config('app.name') }}</p>
                    <p class="text-xs text-muted">Store Order &amp; Inventory Mini-System</p>
                </div>
            </div>

            <p class="tnum text-xs text-faint">
                Laravel {{ app()->version() }} &middot; PHP {{ PHP_MAJOR_VERSION }}.{{ PHP_MINOR_VERSION }}
            </p>
        </div>
    </footer>

    <div x-data x-cloak
         class="no-print pointer-events-none fixed inset-x-0 bottom-0 z-50 flex flex-col items-center gap-2 p-4 sm:items-end">
        <template x-for="toast in $store.toasts.items" :key="toast.id">
            <div x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="translate-y-2 opacity-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-end="opacity-0"
                 class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border bg-surface p-3.5 shadow-lg"
                 :class="toast.tone === 'error'
                     ? 'border-danger-line'
                     : (toast.tone === 'success' ? 'border-success-line' : 'border-line')">
                <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full text-white"
                      :class="toast.tone === 'error'
                          ? 'bg-danger'
                          : (toast.tone === 'success' ? 'bg-success' : 'bg-faint')">
                    <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <template x-if="toast.tone === 'success'"><path d="m5 13 4 4L19 7"/></template>
                        <template x-if="toast.tone !== 'success'"><path d="M12 8v5m0 3h.01"/></template>
                    </svg>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-ink" x-text="toast.title"></p>
                    <p class="mt-0.5 text-sm text-muted" x-text="toast.body" x-show="toast.body"></p>
                </div>
                <button type="button" @click="$store.toasts.dismiss(toast.id)"
                        class="-m-1 shrink-0 rounded p-1 text-faint transition hover:text-body"
                        aria-label="Dismiss">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                         stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
            </div>
        </template>
    </div>
</body>
</html>
