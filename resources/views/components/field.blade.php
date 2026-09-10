@props([
    'label',
    'for' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
])

<div {{ $attributes->only('class') }}>
    <label @if ($for) for="{{ $for }}" @endif class="mb-1.5 flex items-baseline gap-1 text-xs font-semibold text-body">
        {{ $label }}
        @if ($required)
            <span class="text-danger" aria-hidden="true">*</span>
        @endif
        @if ($hint)
            <span class="ml-auto font-normal text-faint">{{ $hint }}</span>
        @endif
    </label>

    {{ $slot }}

    @if ($error)
        <p class="mt-1.5 text-xs text-danger">{{ $error }}</p>
    @endif
</div>
