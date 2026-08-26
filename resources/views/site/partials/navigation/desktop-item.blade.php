@php
    $itemIsCurrent = $navItem->isCurrent($siteCurrentLocale);
    $childIsCurrent = $navItem->children->contains(
        fn ($childItem) => $childItem->isCurrent($siteCurrentLocale)
    );
    $branchIsCurrent = $itemIsCurrent || $childIsCurrent;
    $activeClass = 'site-desktop-nav-link--active';
    $idleClass = 'text-muted-foreground hover:text-foreground';
@endphp

@if($navItem->children->isNotEmpty())
    <div x-data="{ open: false }" @click.outside="open = false" class="relative">
        <button
            type="button"
            @click="open = !open"
            :aria-expanded="open"
            aria-haspopup="true"
            class="site-desktop-nav-link relative inline-flex items-center text-sm font-medium transition-colors {{ $branchIsCurrent ? $activeClass : $idleClass }}"
        >
            <span class="site-desktop-nav-label">{{ $navItem->localized('title') }}</span>
            <i class="fa-solid fa-chevron-down text-[10px]" aria-hidden="true"></i>
        </button>
        <div x-show="open" x-cloak x-transition.origin.top class="site-desktop-nav-dropdown absolute left-0 top-full z-50 mt-2 min-w-[230px] rounded-2xl border border-border bg-background p-2 shadow-lg">
            <a
                href="{{ $navItem->resolvedUrl($siteCurrentLocale) }}"
                target="{{ $navItem->target }}"
                @if($navItem->target === '_blank') rel="noopener noreferrer" @endif
                @if($itemIsCurrent) aria-current="page" @endif
                class="site-desktop-nav-dropdown__link flex items-center rounded-xl px-3 py-2 text-sm font-medium {{ $itemIsCurrent ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted/50' }}"
            >
                <span>{{ $navItem->localized('title') }}</span>
            </a>
            @foreach($navItem->children as $childItem)
                @php($childCurrent = $childItem->isCurrent($siteCurrentLocale))
                <a
                    href="{{ $childItem->resolvedUrl($siteCurrentLocale) }}"
                    target="{{ $childItem->target }}"
                    @if($childItem->target === '_blank') rel="noopener noreferrer" @endif
                    @if($childCurrent) aria-current="page" @endif
                    class="site-desktop-nav-dropdown__link mt-1 flex items-center rounded-xl px-3 py-2 text-sm {{ $childCurrent ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground' }}"
                >
                    <span>{{ $childItem->localized('title') }}</span>
                </a>
            @endforeach
        </div>
    </div>
@else
    <a
        href="{{ $navItem->resolvedUrl($siteCurrentLocale) }}"
        target="{{ $navItem->target }}"
        @if($navItem->target === '_blank') rel="noopener noreferrer" @endif
        @if($itemIsCurrent) aria-current="page" @endif
        class="site-desktop-nav-link relative inline-flex items-center text-sm font-medium transition-colors {{ $itemIsCurrent ? $activeClass : $idleClass }}"
    >
        <span class="site-desktop-nav-label">{{ $navItem->localized('title') }}</span>
    </a>
@endif
