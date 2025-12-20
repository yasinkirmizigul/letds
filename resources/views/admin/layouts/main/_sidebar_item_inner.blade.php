@php
    $type  = $item['type'] ?? 'single';
    $icon  = $item['icon'] ?? '';
    $title = $item['title'] ?? '';
@endphp

@if($type === 'single')
    <div class="kt-menu-item {{ $isActive ? 'active' : '' }}">
        <div class="kt-menu-label gap-[10px] border border-transparent py-[6px] pe-[10px]">
            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
               href="{{ route($item['route']) }}" tabindex="0">
                <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                    <i class="{{ $icon }}"></i>
                </span>
                <span class="kt-menu-title text-sm font-medium text-foreground">{{ $title }}</span>
            </a>
        </div>
    </div>

@elseif($type === 'accordion')
    <div class="kt-menu-item kt-menu-item-accordion {{ $isActive ? 'here show' : '' }}"
         data-kt-menu-item-toggle="accordion"
         data-kt-menu-item-trigger="click">

        <div class="kt-menu-link flex grow cursor-pointer items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
             tabindex="0">
            <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                <i class="{{ $icon }}"></i>
            </span>

            <span class="kt-menu-title kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary text-sm font-medium text-foreground">
                {{ $title }}
            </span>

            <span class="kt-menu-arrow me-[-10px] ms-1 w-[20px] shrink-0 justify-end text-muted-foreground">
                <span class="kt-menu-item-show:hidden inline-flex"><i class="ki-filled ki-plus text-[11px]"></i></span>
                <span class="kt-menu-item-show:inline-flex hidden"><i class="ki-filled ki-minus text-[11px]"></i></span>
            </span>
        </div>

        <div class="kt-menu-accordion relative gap-1 ps-[10px] before:absolute before:bottom-0 before:start-[20px] before:top-0 before:border-s before:border-border">
            @foreach(($item['children'] ?? []) as $child)
                @php
                    $childActive = false;
                    foreach (($child['active'] ?? []) as $p) {
                        if (request()->routeIs($p)) { $childActive = true; break; }
                    }
                @endphp

                @perm($child['perm'])
                <div class="kt-menu-item {{ $childActive ? 'active' : '' }}">
                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                       href="{{ route($child['route']) }}" tabindex="0">
                        <span class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2"></span>
                        <span class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                            {{ $child['title'] ?? '' }}
                        </span>
                    </a>
                </div>
                @endperm
            @endforeach
        </div>
    </div>
@endif
