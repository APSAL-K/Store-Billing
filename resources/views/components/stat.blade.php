@props([
    'label',
    'value',
    'tone' => 'slate',
    'caption' => null,
])

@php
    $tones = [
        'slate' => 'text-ink',
        'brand' => 'text-brand-700',
        'amber' => 'text-warn',
        'rose' => 'text-danger',
    ];
@endphp

<div class="card p-4">
    <p class="text-xs font-medium tracking-wide text-muted uppercase">{{ $label }}</p>
    <p class="tnum mt-1.5 text-2xl font-semibold {{ $tones[$tone] }}">{{ $value }}</p>
    @if ($caption)
        <p class="mt-0.5 text-xs text-faint">{{ $caption }}</p>
    @endif
</div>
