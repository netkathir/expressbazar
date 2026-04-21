@if ($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $start = max(1, $current - 2);
        $end = min($last, $current + 2);
    @endphp

    <nav class="admin-pagination" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <div class="admin-pagination-meta">
            Showing {{ $paginator->firstItem() ?? 0 }}-{{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }}
        </div>

        <div class="admin-pagination-links">
            @if ($paginator->onFirstPage())
                <span class="admin-page-link disabled">{{ __('Previous') }}</span>
            @else
                <a class="admin-page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">{{ __('Previous') }}</a>
            @endif

            @if ($start > 1)
                <a class="admin-page-link" href="{{ $paginator->url(1) }}">1</a>
                @if ($start > 2)
                    <span class="admin-page-link disabled">...</span>
                @endif
            @endif

            @for ($page = $start; $page <= $end; $page++)
                @if ($page === $current)
                    <span class="admin-page-link active">{{ $page }}</span>
                @else
                    <a class="admin-page-link" href="{{ $paginator->url($page) }}">{{ $page }}</a>
                @endif
            @endfor

            @if ($end < $last)
                @if ($end < $last - 1)
                    <span class="admin-page-link disabled">...</span>
                @endif
                <a class="admin-page-link" href="{{ $paginator->url($last) }}">{{ $last }}</a>
            @endif

            @if ($paginator->hasMorePages())
                <a class="admin-page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">{{ __('Next') }}</a>
            @else
                <span class="admin-page-link disabled">{{ __('Next') }}</span>
            @endif
        </div>
    </nav>
@endif
