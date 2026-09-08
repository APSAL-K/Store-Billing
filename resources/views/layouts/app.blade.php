<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Store Billing')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-100 text-slate-800 antialiased">
    <header class="bg-slate-900 text-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <a href="{{ route('billing.index') }}" class="flex items-baseline gap-3">
                <span class="text-lg font-semibold tracking-tight">Store Billing</span>
                <span class="text-slate-400">&mdash;</span>
                <span class="text-slate-300">@yield('subtitle', 'New Order')</span>
            </a>
            <p class="text-xs text-slate-400">{{ now()->format('d M Y') }}</p>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-6 py-8">
        @yield('content')
    </main>
</body>
</html>
