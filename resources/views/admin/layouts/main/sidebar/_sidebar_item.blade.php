@php
    $type = $item['type'] ?? 'single';

    // --- Active patterns: item.active + (accordion ise) children.active birleşik ---
    $patterns = (array)($item['active'] ?? []);

    if ($type === 'accordion' && !empty($item['children']) && is_array($item['children'])) {
        foreach ($item['children'] as $child) {
            foreach ((array)($child['active'] ?? []) as $p) {
                $patterns[] = $p;
            }
        }
    }

    $isActive = false;
    foreach (array_unique($patterns) as $p) {
        if ($p && request()->routeIs($p)) { $isActive = true; break; }
    }

    // --- Access guard ---
    $guard   = $item['guard']   ?? null; // 'admin'
    $permAny = $item['permAny'] ?? null; // array
    $perm    = $item['perm']    ?? null; // string
@endphp

{{-- Guard (admin) --}}
@if($guard === 'admin')
    @admin
    @include('admin.layouts.main.sidebar._sidebar_item_inner', compact('item','isActive'))
    @endadmin

    {{-- Permission Any --}}
@elseif($permAny)
    @permAny($permAny)
    @include('admin.layouts.main.sidebar._sidebar_item_inner', compact('item','isActive'))
    @endpermAny

    {{-- Permission Single --}}
@elseif($perm)
    @perm($perm)
    @include('admin.layouts.main.sidebar._sidebar_item_inner', compact('item','isActive'))
    @endperm

    {{-- No guard/perm => render --}}
@else
    @include('admin.layouts.main.sidebar._sidebar_item_inner', compact('item','isActive'))
@endif
