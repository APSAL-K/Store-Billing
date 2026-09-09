@props([
    'label',
    'for' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
])

<div {{ $attributes->only('class') }}>
    <label @if ($for) for="{{ $for }}" @endif class="mb-1.5 flex items-baseline gap-1 text-xs font-semibold text-slate-700">
        {{ $label }}
        @if ($required)
            <span class="text-rose-500" aria-hidden="true">*</span>
        @endif
        @if ($hint)
            <span class="ml-auto font-normal text-slate-400">{{ $hint }}</span>
        @endif
    </label>

    {{ $slot }}

    @if ($error)
        <p class="mt-1.5 text-xs text-rose-600">{{ $error }}</p>
    @endif
</div>
