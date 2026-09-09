@props([
    'label',
    'value',
    'tone' => 'slate',
    'caption' => null,
])

@php
    $tones = [
        'slate' => 'text-slate-900',
        'brand' => 'text-brand-700',
        'amber' => 'text-amber-600',
        'rose' => 'text-rose-600',
    ];
@endphp

<div class="card p-4">
    <p class="text-xs font-medium tracking-wide text-slate-500 uppercase">{{ $label }}</p>
    <p class="tnum mt-1.5 text-2xl font-semibold {{ $tones[$tone] }}">{{ $value }}</p>
    @if ($caption)
        <p class="mt-0.5 text-xs text-slate-400">{{ $caption }}</p>
    @endif
</div>
