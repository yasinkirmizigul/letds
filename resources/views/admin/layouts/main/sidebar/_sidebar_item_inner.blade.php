@php
    $type  = $item['type'] ?? 'single';
    $icon  = $item['icon'] ?? '';
    $title = $item['title'] ?? '';
    $style = $item['style'] ?? '';
@endphp

@if($type === 'single')
    <div class="kt-menu-item {{ $isActive ? 'active' : '' }}">
        <div class="kt-menu-label gap-[10px] border border-transparent">
            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[6px] pe-[10px] ps-[10px] hover:rounded-lg"
               href="{{ isset($item['route']) ? route($item['route']) : '#' }}"
               tabindex="0">
                <span class="w-[20px] items-start text-muted-foreground">
                    <i class="{{ $icon }}"></i>
                </span>
                <span class="kt-menu-title text-sm font-medium text-foreground" style="{{ $style }}">{{ $title }}</span>
            </a>
        </div>
    </div>

@elseif($type === 'accordion')
    <div class="kt-menu-item kt-menu-item-accordion {{ $isActive ? 'here show' : '' }}"
         data-kt-menu-item-toggle="accordion"
         data-kt-menu-item-trigger="click">

        <div class="kt-menu-link flex grow cursor-pointer items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
             tabindex="0">
            <span class="w-[20px] items-start text-muted-foreground">
                <i class="{{ $icon }}"></i>
            </span>

            <span class="kt-menu-title kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary text-sm font-medium text-foreground">
                {{ $title }}
            </span>

            <span class="kt-menu-arrow me-[-10px] ms-1 w-[20px] shrink-0 justify-end text-muted-foreground">
                <span class="kt-menu-item-show:hidden inline-flex">
                    <i class="ki-filled ki-plus text-[11px]"></i>
                </span>
                <span class="kt-menu-item-show:inline-flex hidden">
                    <i class="ki-filled ki-minus text-[11px]"></i>
                </span>
            </span>
        </div>

        <div class="kt-menu-accordion relative gap-1 ps-[10px] before:absolute before:bottom-0 before:start-[20px] before:top-0 before:border-s before:border-border">
            @foreach(($item['children'] ?? []) as $child)
                @php
                    $childTitle  = $child['title'] ?? '';
                    $childRoute  = $child['route'] ?? null;

                    $childGuard  = $child['guard'] ?? null;
                    $childPerm   = $child['perm'] ?? null;
                    $childPermAny= $child['permAny'] ?? null;

                    // child aktiflik: sadece kendi active patterns
                    $childIsActive = false;
                    foreach ((array)($child['active'] ?? []) as $p) {
                        if ($p && request()->routeIs($p)) { $childIsActive = true; break; }
                    }
                @endphp

                {{-- Child guard/perm wrapper --}}
                @if($childGuard === 'admin')
                    @admin
                    @include('admin.layouts.main.sidebar._sidebar_item_inner_child', [
                        'childTitle' => $childTitle,
                        'childRoute' => $childRoute,
                        'childIsActive' => $childIsActive,
                    ])
                    @endadmin

                @elseif($childPermAny)
                    @permAny($childPermAny)
                    @include('admin.layouts.main.sidebar._sidebar_item_inner_child', [
                        'childTitle' => $childTitle,
                        'childRoute' => $childRoute,
                        'childIsActive' => $childIsActive,
                    ])
                    @endpermAny

                @elseif($childPerm)
                    @perm($childPerm)
                    @include('admin.layouts.main.sidebar._sidebar_item_inner_child', [
                        'childTitle' => $childTitle,
                        'childRoute' => $childRoute,
                        'childIsActive' => $childIsActive,
                    ])
                    @endperm

                @else
                    @include('admin.layouts.main.sidebar._sidebar_item_inner_child', [
                        'childTitle' => $childTitle,
                        'childRoute' => $childRoute,
                        'childIsActive' => $childIsActive,
                    ])
                @endif
            @endforeach
        </div>
    </div>
@endif
