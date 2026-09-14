@if ($paginator->hasPages())
    <nav class="hafez-pagination" dir="rtl" aria-label="التنقل بين الصفحات">
        <div class="hafez-pagination__controls">
            @if ($paginator->onFirstPage())
                <span class="page-link disabled" aria-disabled="true">السابق</span>
            @else
                <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">السابق</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="hafez-pagination__ellipsis" aria-hidden="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="page-link active" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">التالي</a>
            @else
                <span class="page-link disabled" aria-disabled="true">التالي</span>
            @endif
        </div>
        <p class="hafez-pagination__summary mb-0">
            عرض {{ $paginator->firstItem() ?? 0 }} إلى {{ $paginator->lastItem() ?? 0 }} من أصل {{ $paginator->total() }} نتيجة
        </p>
    </nav>
@endif
