@props(['change'])

@if ($change === null)
    <span class="badge bg-sunken text-faint">new</span>
@else
    @php($up = $change >= 0)
    <span class="badge {{ $up ? 'bg-success-soft text-success' : 'bg-danger-soft text-danger' }}">
        <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            @if ($up)
                <path d="M4 17 10 11l4 4 6-6"/><path d="M15 6h5v5"/>
            @else
                <path d="M4 7 10 13l4-4 6 6"/><path d="M15 18h5v-5"/>
            @endif
        </svg>
        <span class="tnum">{{ number_format(abs($change), $change >= 100 ? 0 : 1) }}%</span>
    </span>
@endif
