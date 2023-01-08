@if ($paginator->hasPages())
    <nav aria-label="Contacts Page Navigation">
        <ul class="pagination justify-content-center m-0">
            @if ($paginator->onFirstPage())
                <li class="paginate_button page-item previous disabled" id="example1_previous">
                    <a href="#" aria-controls="example1" data-dt-idx="0" tabindex="0" class="page-link">Previous</a>
                </li>
            @else
                <li class="paginate_button page-item previous" id="example1_previous">
                    <a href="{{ $paginator->previousPageUrl() }}" aria-controls="example1" data-dt-idx="0" tabindex="0" class="page-link">Previous</a>
                </li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))

                    <li class="page-item"><a class="page-link" href="#">{{ $element }}</a></li>

                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())

                            <li class="page-item active"><a class="page-link" href="#">{{ $page }}</a></li>

                        @else
                            <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li class="paginate_button page-item next" id="example1_next">
                    <a href="{{ $paginator->nextPageUrl() }}" aria-controls="example1" data-dt-idx="7" tabindex="0" class="page-link">Next</a>
                </li>
            @else
                <li class="paginate_button page-item next disabled" id="example1_next">
                    <a href="#" aria-controls="example1" data-dt-idx="7" tabindex="0" class="page-link">Next</a>
                </li>
            @endif
        </ul>
    </nav>
@endif
