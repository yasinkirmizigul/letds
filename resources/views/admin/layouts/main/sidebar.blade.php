@php
    $u = auth()->user();

    $isDashboard = request()->routeIs('admin.dashboard');
    $isBlog      = request()->routeIs('admin.blog.*');
    $isMedia     = request()->routeIs('admin.media.*');
    $isCategory  = request()->routeIs('admin.categories.*');
    $isUsers     = request()->routeIs('admin.users.*');
    $isRoles     = request()->routeIs('admin.roles.*');
    $isPerms     = request()->routeIs('admin.permissions.*');

    $canUsers = $u?->canAccess('users.view') ?? false;
    $canRoles = $u?->canAccess('roles.view') ?? false;
    $canPerms = $u?->canAccess('permissions.view') ?? false;

    $profileOpen = ($isUsers || $isRoles || $isPerms) && ($canUsers || $canRoles || $canPerms);
@endphp

<div
    class="kt-sidebar fixed bottom-0 top-0 z-20 hidden shrink-0 flex-col items-stretch border-e border-e-border bg-background [--kt-drawer-enable:true] lg:flex lg:[--kt-drawer-enable:false]"
    data-kt-drawer="true"
    data-kt-drawer-class="kt-drawer kt-drawer-start top-0 bottom-0"
    id="sidebar">

    <div class="kt-sidebar-header relative hidden shrink-0 items-center justify-between px-3 lg:flex lg:px-6"
         id="sidebar_header">

        <button
            class="kt-btn kt-btn-outline kt-btn-icon absolute start-full top-2/4 size-[30px] -translate-x-2/4 -translate-y-2/4 rtl:translate-x-2/4"
            data-kt-toggle="body"
            data-kt-toggle-class="kt-sidebar-collapse"
            id="sidebar_toggle">
            <i class="ki-filled ki-black-left-line kt-toggle-active:rotate-180 rtl:translate rtl:kt-toggle-active:rotate-0 transition-all duration-300 rtl:rotate-180"></i>
        </button>
    </div>

    <div class="kt-sidebar-content flex shrink-0 grow py-5 pe-2" id="sidebar_content">
        <div class="kt-scrollable-y-hover flex shrink-0 grow pe-1 ps-2 lg:pe-3 lg:ps-5"
             data-kt-scrollable="true"
             data-kt-scrollable-dependencies="#sidebar_header"
             data-kt-scrollable-height="auto"
             data-kt-scrollable-offset="0px"
             data-kt-scrollable-wrappers="#sidebar_content"
             id="sidebar_scrollable">

            <div class="flex grow flex-col gap-1"
                 data-kt-menu="true"
                 data-kt-menu-accordion-expand-all="false"
                 id="sidebar_menu">
                @php($menu = config('admin_menu', []))

                @foreach($menu as $item)
                    @include('admin.layouts.main.sidebar._sidebar_item', ['item' => $item])
                @endforeach

            </div>
        </div>
    </div>
</div>
