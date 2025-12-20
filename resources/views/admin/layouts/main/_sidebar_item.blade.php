@php
    $type = $item['type'] ?? 'single';

    $isActive = false;
    foreach (($item['active'] ?? []) as $p) {
        if (request()->routeIs($p)) { $isActive = true; break; }
    }

    $permAny = $item['permAny'] ?? null; // array
    $guard   = $item['guard'] ?? null;   // 'admin' vb.
@endphp

{{-- Guard (admin) --}}
@if($guard === 'admin')
    @admin
    @include('admin.layouts.main._sidebar_item_inner', ['item' => $item, 'isActive' => $isActive])
    @endadmin
@elseif($permAny)
    @permAny($permAny)
    @include('admin.layouts.main._sidebar_item_inner', ['item' => $item, 'isActive' => $isActive])
    @endpermAny
@else
    @include('admin.layouts.main._sidebar_item_inner', ['item' => $item, 'isActive' => $isActive])
@endif
