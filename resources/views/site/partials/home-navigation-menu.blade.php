<div class="home-mobile-menu" data-home-navigation data-open="false">
    <button
        type="button"
        class="home-mobile-menu__trigger"
        aria-label="Site menüsünü aç"
        aria-controls="home-navigation-panel"
        aria-expanded="false"
        data-home-navigation-toggle
    >
        <span aria-hidden="true"></span>
        <span aria-hidden="true"></span>
        <span aria-hidden="true"></span>
    </button>

    <div id="home-navigation-panel" class="home-mobile-menu__panel" aria-hidden="true" data-home-navigation-panel inert>
        <nav class="home-mobile-menu__links" aria-label="Ana menü">
            @foreach($sitePrimaryNavigation as $navItem)
                @php
                    $itemIsCurrent = $navItem->isCurrent($locale);
                    $resolvedUrl = $navItem->resolvedUrl($locale);
                    $sectionId = parse_url($resolvedUrl, PHP_URL_FRAGMENT);
                @endphp
                <a
                    href="{{ $resolvedUrl }}"
                    class="home-mobile-menu__link {{ $itemIsCurrent ? 'is-current' : '' }}"
                    data-site-section-link="{{ $sectionId ?: '' }}"
                    @if($itemIsCurrent) aria-current="page" @endif
                    target="{{ $navItem->target }}"
                    @if($navItem->target === '_blank') rel="noopener noreferrer" @endif
                >
                    {{ $navItem->localized('title', $locale) }}
                </a>
                @foreach($navItem->children as $childItem)
                    @php
                        $childIsCurrent = $childItem->isCurrent($locale);
                    @endphp
                    <a
                        href="{{ $childItem->resolvedUrl($locale) }}"
                        class="home-mobile-menu__link home-mobile-menu__link--child {{ $childIsCurrent ? 'is-current' : '' }}"
                        @if($childIsCurrent) aria-current="page" @endif
                        target="{{ $childItem->target }}"
                        @if($childItem->target === '_blank') rel="noopener noreferrer" @endif
                    >
                        {{ $childItem->localized('title', $locale) }}
                    </a>
                @endforeach
            @endforeach
        </nav>

    </div>
</div>
