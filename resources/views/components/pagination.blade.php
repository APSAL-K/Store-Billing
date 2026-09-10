@props([
    'paginator',
    'label' => 'records',
])

@php
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();

    $pages = collect(range(1, $last))
        ->filter(fn (int $page): bool => $page === 1
            || $page === $last
            || abs($page - $current) <= 1)
        ->values();

    $window = [];
    $previous = 0;

    foreach ($pages as $page) {
        if ($previous > 0 && $page - $previous > 1) {
            $window[] = null;
        }

        $window[] = $page;
        $previous = $page;
    }
@endphp

@if ($paginator->total() > 0)
    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-5 py-3">
        <div class="flex items-center gap-3">
            <p class="tnum text-xs whitespace-nowrap text-muted">
                <span class="font-medium text-body">{{ $paginator->firstItem() }}&ndash;{{ $paginator->lastItem() }}</span>
                of {{ number_format($paginator->total()) }} {{ $label }}
            </p>

            @if ($paginator->total() > \App\Support\PerPage::OPTIONS[0])
                <form method="GET" class="flex items-center gap-1.5">
                    @foreach (request()->except(['per_page', 'page']) as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach

                    <label for="per-page-{{ $label }}" class="text-xs text-faint">Show</label>
                    <select id="per-page-{{ $label }}" name="per_page" onchange="this.form.submit()"
                            class="tnum rounded-md border border-line bg-surface py-1 pr-7 pl-2 text-xs font-semibold text-body shadow-xs transition hover:border-line-strong focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 focus:outline-none">
                        @foreach (\App\Support\PerPage::OPTIONS as $option)
                            <option value="{{ $option }}" @selected($paginator->perPage() === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>

        @if ($paginator->hasPages())
            <nav class="flex items-center gap-1" aria-label="Pagination">
                @if ($paginator->onFirstPage())
                    <span class="btn-row cursor-not-allowed opacity-40" aria-hidden="true">Prev</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn-row">Prev</a>
                @endif

                <span class="hidden items-center gap-1 sm:flex">
                    @foreach ($window as $page)
                        @if ($page === null)
                            <span class="px-1 text-xs text-faint">&hellip;</span>
                        @elseif ($page === $current)
                            <span class="btn-row btn-row-primary tnum" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $paginator->url($page) }}" class="btn-row tnum">{{ $page }}</a>
                        @endif
                    @endforeach
                </span>

                <span class="tnum px-1 text-xs text-muted sm:hidden">{{ $current }} / {{ $last }}</span>

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn-row">Next</a>
                @else
                    <span class="btn-row cursor-not-allowed opacity-40" aria-hidden="true">Next</span>
                @endif
            </nav>
        @endif
    </div>
@endif
