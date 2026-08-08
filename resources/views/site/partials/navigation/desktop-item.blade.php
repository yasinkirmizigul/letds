@php
    $itemIsCurrent = $navItem->isCurrent($siteCurrentLocale);
    $childIsCurrent = $navItem->children->contains(
        fn ($childItem) => $childItem->isCurrent($siteCurrentLocale)
    );
    $branchIsCurrent = $itemIsCurrent || $childIsCurrent;
    $activeClass = "text-primary after:absolute after:inset-x-3 after:-bottom-px after:h-0.5 after:rounded-full after:bg-primary after:content-['']";
    $idleClass = 'text-muted-foreground hover:text-foreground';
@endphp

@if($navItem->children->isNotEmpty())
    <div x-data="{ open: false }" @click.outside="open = false" class="relative">
        <button
            type="button"
            @click="open = !open"
            :aria-expanded="open"
            aria-haspopup="true"
            class="relative inline-flex items-center gap-2 px-3 py-2 text-sm font-medium transition-colors {{ $branchIsCurrent ? $activeClass : $idleClass }}"
        >
            @if(filled($navItem->icon_class))<i class="{{ $navItem->icon_class }}" aria-hidden="true"></i>@endif
            <span>{{ $navItem->localized('title') }}</span>
            <i class="ki-outline ki-down text-[10px]" aria-hidden="true"></i>
        </button>
        <div x-show="open" x-cloak x-transition.origin.top class="absolute left-0 top-full z-50 mt-2 min-w-[230px] rounded-2xl border border-border bg-background p-2 shadow-lg">
            <a
                href="{{ $navItem->resolvedUrl($siteCurrentLocale) }}"
                target="{{ $navItem->target }}"
                @if($navItem->target === '_blank') rel="noopener noreferrer" @endif
                @if($itemIsCurrent) aria-current="page" @endif
                class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium {{ $itemIsCurrent ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted/50' }}"
            >
                @if(filled($navItem->icon_class))<i class="{{ $navItem->icon_class }}" aria-hidden="true"></i>@endif
                <span>{{ $navItem->localized('title') }}</span>
            </a>
            @foreach($navItem->children as $childItem)
                @php($childCurrent = $childItem->isCurrent($siteCurrentLocale))
                <a
                    href="{{ $childItem->resolvedUrl($siteCurrentLocale) }}"
                    target="{{ $childItem->target }}"
                    @if($childItem->target === '_blank') rel="noopener noreferrer" @endif
                    @if($childCurrent) aria-current="page" @endif
                    class="mt-1 flex items-center gap-2 rounded-xl px-3 py-2 text-sm {{ $childCurrent ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground' }}"
                >
                    @if(filled($childItem->icon_class))<i class="{{ $childItem->icon_class }}" aria-hidden="true"></i>@endif
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
        class="relative inline-flex items-center gap-2 px-3 py-2 text-sm font-medium transition-colors {{ $itemIsCurrent ? $activeClass : $idleClass }}"
    >
        @if(filled($navItem->icon_class))<i class="{{ $navItem->icon_class }}" aria-hidden="true"></i>@endif
        <span>{{ $navItem->localized('title') }}</span>
    </a>
@endif
