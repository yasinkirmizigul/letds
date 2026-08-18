@php($itemIsCurrent = $navItem->isCurrent($siteCurrentLocale))
<a
    href="{{ $navItem->resolvedUrl($siteCurrentLocale) }}"
    target="{{ $navItem->target }}"
    @if($navItem->target === '_blank') rel="noopener noreferrer" @endif
    @if($itemIsCurrent) aria-current="page" @endif
    class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium {{ $itemIsCurrent ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted/60' }}"
>
    @if(filled($navItem->icon_class))<i class="{{ \App\Support\Site\SiteIcon::classes($navItem->icon_class) }}" aria-hidden="true"></i>@endif
    <span>{{ $navItem->localized('title') }}</span>
</a>

@foreach($navItem->children as $childItem)
    @php($childCurrent = $childItem->isCurrent($siteCurrentLocale))
    <a
        href="{{ $childItem->resolvedUrl($siteCurrentLocale) }}"
        target="{{ $childItem->target }}"
        @if($childItem->target === '_blank') rel="noopener noreferrer" @endif
        @if($childCurrent) aria-current="page" @endif
        class="flex items-center gap-2 rounded-xl px-3 py-2 pl-7 text-sm {{ $childCurrent ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-muted/60 hover:text-foreground' }}"
    >
        @if(filled($childItem->icon_class))<i class="{{ \App\Support\Site\SiteIcon::classes($childItem->icon_class) }}" aria-hidden="true"></i>@endif
        <span>{{ $childItem->localized('title') }}</span>
    </a>
@endforeach
