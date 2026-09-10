@props([
    'title',
    'description' => null,
    'show' => 'open',
    'width' => 'max-w-md',
])

<div x-show="{{ $show }}" x-cloak x-transition.opacity @keydown.escape.window="{{ $show }} = false"
     class="no-print fixed inset-0 z-40 flex items-center justify-center bg-ink/50 p-4"
     role="dialog" aria-modal="true">
    <div @click.outside="{{ $show }} = false"
         class="w-full {{ $width }} rounded-xl bg-surface p-5 shadow-xl"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="scale-95 opacity-0">
        <h2 class="text-sm font-semibold text-ink">{{ $title }}</h2>
        @if ($description)
            <p class="mt-1 text-sm text-muted">{{ $description }}</p>
        @endif

        {{ $slot }}
    </div>
</div>
