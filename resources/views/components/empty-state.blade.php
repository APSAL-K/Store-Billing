@props([
    'title',
    'description' => null,
])

<div class="flex flex-col items-center justify-center px-6 py-16 text-center">
    <div class="flex size-11 items-center justify-center rounded-full bg-sunken text-faint">
        {{ $icon ?? '' }}
    </div>
    <p class="mt-3 text-sm font-semibold text-body">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 max-w-sm text-sm text-muted">{{ $description }}</p>
    @endif
    @isset($action)
        <div class="mt-4">{{ $action }}</div>
    @endisset
</div>
