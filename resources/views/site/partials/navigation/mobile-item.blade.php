@php
    $itemIsCurrent = $navItem->isCurrent($siteCurrentLocale);
    $resolvedUrl = $navItem->resolvedUrl($siteCurrentLocale);
    $sectionId = parse_url($resolvedUrl, PHP_URL_FRAGMENT);
@endphp
<a
    href="{{ $resolvedUrl }}"
    target="{{ $navItem->target }}"
    data-site-section-link="{{ $sectionId ?: '' }}"
    @if($navItem->target === '_blank') rel="noopener noreferrer" @endif
    @if($itemIsCurrent) aria-current="page" @endif
    class="site-mobile-nav__link flex items-center rounded-xl px-3 py-2 text-sm font-medium {{ $itemIsCurrent ? 'site-mobile-nav__link--active' : 'text-foreground hover:bg-muted/60' }}"
>
    <span>{{ $navItem->localized('title') }}</span>
</a>

@foreach($navItem->children as $childItem)
    @php
        $childCurrent = $childItem->isCurrent($siteCurrentLocale);
    @endphp
    <a
        href="{{ $childItem->resolvedUrl($siteCurrentLocale) }}"
        target="{{ $childItem->target }}"
        @if($childItem->target === '_blank') rel="noopener noreferrer" @endif
        @if($childCurrent) aria-current="page" @endif
        class="site-mobile-nav__link site-mobile-nav__link--child flex items-center rounded-xl px-3 py-2 pl-7 text-sm {{ $childCurrent ? 'site-mobile-nav__link--active' : 'text-muted-foreground hover:bg-muted/60 hover:text-foreground' }}"
    >
        <span>{{ $childItem->localized('title') }}</span>
    </a>
@endforeach
