@props([
    'paginator',
    'label' => 'records',
])

@if ($paginator->total() > 0)
    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-5 py-3">
        <p class="tnum text-xs text-slate-500">
            Showing {{ $paginator->firstItem() }}&ndash;{{ $paginator->lastItem() }}
            of {{ number_format($paginator->total()) }} {{ $label }}
        </p>

        @if ($paginator->hasPages())
            <nav class="flex items-center gap-1" aria-label="Pagination">
                @if ($paginator->onFirstPage())
                    <span class="btn-row cursor-not-allowed opacity-40">Previous</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn-row">Previous</a>
                @endif

                @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="btn-row btn-row-primary tnum" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="btn-row tnum">{{ $page }}</a>
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn-row">Next</a>
                @else
                    <span class="btn-row cursor-not-allowed opacity-40">Next</span>
                @endif
            </nav>
        @endif
    </div>
@endif
