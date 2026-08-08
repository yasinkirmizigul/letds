<a
    href="{{ $navItem->resolvedUrl($siteCurrentLocale) }}"
    target="{{ $navItem->target }}"
    @if($navItem->target === '_blank') rel="noopener noreferrer" @endif
    @if($navItem->isCurrent($siteCurrentLocale)) aria-current="page" @endif
    class="flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
>
    @if(filled($navItem->icon_class))<i class="{{ $navItem->icon_class }}" aria-hidden="true"></i>@endif
    <span>{{ $navItem->localized('title') }}</span>
</a>

@foreach($navItem->children as $childItem)
    <a
        href="{{ $childItem->resolvedUrl($siteCurrentLocale) }}"
        target="{{ $childItem->target }}"
        @if($childItem->target === '_blank') rel="noopener noreferrer" @endif
        @if($childItem->isCurrent($siteCurrentLocale)) aria-current="page" @endif
        class="flex items-center gap-2 pl-4 text-sm text-muted-foreground hover:text-foreground"
    >
        @if(filled($childItem->icon_class))<i class="{{ $childItem->icon_class }}" aria-hidden="true"></i>@endif
        <span>{{ $childItem->localized('title') }}</span>
    </a>
@endforeach
