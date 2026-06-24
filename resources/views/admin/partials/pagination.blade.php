@if ($paginator->hasPages())
    @php
        function pageUrl($paginator, $page) {
            $query = request()->except('page');

            if ($page == 1) {
                return count($query)
                    ? request()->url() . '?' . http_build_query($query)
                    : request()->url();
            }

            return $paginator->url($page);
        }
    @endphp

    <nav class="mt-3">
        <ul class="pagination flex-wrap justify-content-center">

            {{-- First + Previous --}}
            @if (!$paginator->onFirstPage())
                <li class="page-item">
                    <a class="page-link"
                       href="{{ pageUrl($paginator, 1) }}"
                       rel="first"
                       aria-label="« First">
                        &lsaquo;&lsaquo;
                    </a>
                </li>

                <li class="page-item">
                    <a class="page-link"
                       href="{{ pageUrl($paginator, $paginator->currentPage() - 1) }}"
                       rel="prev"
                       aria-label="@lang('pagination.previous')">
                        &lsaquo;
                    </a>
                </li>
            @endif

            {{-- Pages --}}
            @foreach ($elements as $element)

                {{-- Dots --}}
                @if (is_string($element))
                    <li class="page-item disabled">
                        <span class="page-link">{{ $element }}</span>
                    </li>
                @endif

                {{-- Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active">
                                <span class="page-link">{{ $page }}</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ pageUrl($paginator, $page) }}">
                                    {{ $page }}
                                </a>
                            </li>
                        @endif
                    @endforeach
                @endif

            @endforeach

            {{-- Next + Last --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link"
                       href="{{ pageUrl($paginator, $paginator->currentPage() + 1) }}"
                       rel="next"
                       aria-label="@lang('pagination.next')">
                        &rsaquo;
                    </a>
                </li>

                <li class="page-item">
                    <a class="page-link"
                       href="{{ pageUrl($paginator, $paginator->lastPage()) }}"
                       rel="last"
                       aria-label="Last »">
                        &rsaquo;&rsaquo;
                    </a>
                </li>
            @endif

        </ul>
    </nav>
@endif
