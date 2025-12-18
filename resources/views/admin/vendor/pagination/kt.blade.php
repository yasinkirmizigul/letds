@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Sayfalama" class="flex items-center justify-center md:justify-end">
        <ul class="inline-flex items-center gap-1">

            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li>
                    <span class="kt-btn kt-btn-sm kt-btn-light opacity-60 pointer-events-none" aria-disabled="true" aria-label="Önceki">
                        <i class="ki-filled ki-left"></i>
                    </span>
                </li>
            @else
                <li>
                    <a class="kt-btn kt-btn-sm kt-btn-light" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Önceki">
                        <i class="ki-filled ki-left"></i>
                    </a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)

                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li>
                        <span class="kt-btn kt-btn-sm kt-btn-mono opacity-70 pointer-events-none" aria-disabled="true">
                            {{ $element }}
                        </span>
                    </li>

                    {{-- Array Of Links --}}
                @else
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li>
                                <span class="kt-btn kt-btn-sm kt-btn-primary pointer-events-none" aria-current="page">
                                    {{ $page }}
                                </span>
                            </li>
                        @else
                            <li>
                                <a class="kt-btn kt-btn-sm kt-btn-light" href="{{ $url }}" aria-label="Sayfa {{ $page }}">
                                    {{ $page }}
                                </a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li>
                    <a class="kt-btn kt-btn-sm kt-btn-light" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Sonraki">
                        <i class="ki-filled ki-right"></i>
                    </a>
                </li>
            @else
                <li>
                    <span class="kt-btn kt-btn-sm kt-btn-light opacity-60 pointer-events-none" aria-disabled="true" aria-label="Sonraki">
                        <i class="ki-filled ki-right"></i>
                    </span>
                </li>
            @endif

        </ul>
    </nav>
@endif
