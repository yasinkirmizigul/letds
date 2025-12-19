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

            <div class="kt-menu flex grow flex-col gap-1"
                 data-kt-menu="true"
                 data-kt-menu-accordion-expand-all="false"
                 id="sidebar_menu">

                {{-- Dashboard --}}
                @admin
                <div class="kt-menu-item {{ $isDashboard ? 'active' : '' }}">
                    <div class="kt-menu-label gap-[10px] border border-transparent py-[6px] pe-[10px]">
                        <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                           href="{{ route('admin.dashboard') }}" tabindex="0">
                                <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                                    <i class="ki-filled ki-element-11 text-lg"></i>
                                </span>
                            <span class="kt-menu-title text-sm font-medium text-foreground">Dashboard</span>
                        </a>
                    </div>
                </div>
                @endadmin

                {{-- Blog --}}
                @perm('blog.view')
                <div class="kt-menu-item kt-menu-item-accordion {{ $isBlog ? 'here show' : '' }}"
                     data-kt-menu-item-toggle="accordion"
                     data-kt-menu-item-trigger="click">

                    <div
                        class="kt-menu-link flex grow cursor-pointer items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                        tabindex="0">
                            <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                                <i class="ki-filled ki-book text-lg"></i>
                            </span>

                        <span
                            class="kt-menu-title kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary text-sm font-medium text-foreground">
                                Blog
                            </span>

                        <span class="kt-menu-arrow me-[-10px] ms-1 w-[20px] shrink-0 justify-end text-muted-foreground">
                                <span class="kt-menu-item-show:hidden inline-flex"><i
                                        class="ki-filled ki-plus text-[11px]"></i></span>
                                <span class="kt-menu-item-show:inline-flex hidden"><i
                                        class="ki-filled ki-minus text-[11px]"></i></span>
                            </span>
                    </div>

                    <div
                        class="kt-menu-accordion relative gap-1 ps-[10px] before:absolute before:bottom-0 before:start-[20px] before:top-0 before:border-s before:border-border">
                        <div class="kt-menu-item {{ $isBlog ? 'active' : '' }}">
                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                               href="{{ route('admin.blog.index') }}" tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2"></span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                        Yazılar
                                    </span>
                            </a>
                        </div>
                    </div>
                </div>
                @endperm
                @permAny('media.view')
                <div class="kt-menu-item kt-menu-item-accordion {{ $isMedia ? 'here show' : '' }}"
                     data-kt-menu-item-toggle="accordion"
                     data-kt-menu-item-trigger="click">

                    <div
                        class="kt-menu-link flex grow cursor-pointer items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                        tabindex="0">
                            <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                                <i class="ki-filled ki-screen text-lg"></i>
                            </span>

                        <span
                            class="kt-menu-title kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary text-sm font-medium text-foreground">
                                Medya
                            </span>

                        <span class="kt-menu-arrow me-[-10px] ms-1 w-[20px] shrink-0 justify-end text-muted-foreground">
                                <span class="kt-menu-item-show:hidden inline-flex"><i
                                        class="ki-filled ki-plus text-[11px]"></i></span>
                                <span class="kt-menu-item-show:inline-flex hidden"><i
                                        class="ki-filled ki-minus text-[11px]"></i></span>
                            </span>
                    </div>

                    <div
                        class="kt-menu-accordion relative gap-1 ps-[10px] before:absolute before:bottom-0 before:start-[20px] before:top-0 before:border-s before:border-border">

                        @perm('media.view')
                        <div class="kt-menu-item {{ $isMedia ? 'active' : '' }}">
                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                               href="{{ route('admin.media.index') }}" tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2"></span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Medyalar
                                        </span>
                            </a>
                        </div>
                        @endperm

                    </div>
                </div>
                @endpermAny

                {{-- Kategoriler --}}
                @perm('category.view')
                <div class="kt-menu-item kt-menu-item-accordion {{ $isCategory ? 'here show' : '' }}"
                     data-kt-menu-item-toggle="accordion"
                     data-kt-menu-item-trigger="click">

                    <div
                        class="kt-menu-link flex grow cursor-pointer items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                        tabindex="0">
                            <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                                <i class="ki-filled ki-document text-lg"></i>
                            </span>

                        <span
                            class="kt-menu-title kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary text-sm font-medium text-foreground">
                                Kategoriler
                            </span>

                        <span class="kt-menu-arrow me-[-10px] ms-1 w-[20px] shrink-0 justify-end text-muted-foreground">
                                <span class="kt-menu-item-show:hidden inline-flex"><i
                                        class="ki-filled ki-plus text-[11px]"></i></span>
                                <span class="kt-menu-item-show:inline-flex hidden"><i
                                        class="ki-filled ki-minus text-[11px]"></i></span>
                            </span>
                    </div>

                    <div
                        class="kt-menu-accordion relative gap-1 ps-[10px] before:absolute before:bottom-0 before:start-[20px] before:top-0 before:border-s before:border-border">
                        <div class="kt-menu-item {{ $isCategory ? 'active' : '' }}">
                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                               href="{{ route('admin.categories.index') }}" tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2"></span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                        Kategoriler
                                    </span>
                            </a>
                        </div>
                    </div>
                </div>
                @endperm

                {{-- Kullanıcılar (Users/Roles/Permissions) --}}
                @permAny('users.view','roles.view','permissions.view')
                <div class="kt-menu-item kt-menu-item-accordion {{ $profileOpen ? 'here show' : '' }}"
                     data-kt-menu-item-toggle="accordion"
                     data-kt-menu-item-trigger="click">

                    <div
                        class="kt-menu-link flex grow cursor-pointer items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                        tabindex="0">
                            <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                                <i class="ki-filled ki-profile-circle text-lg"></i>
                            </span>

                        <span
                            class="kt-menu-title kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary text-sm font-medium text-foreground">
                                Kullanıcılar
                            </span>

                        <span class="kt-menu-arrow me-[-10px] ms-1 w-[20px] shrink-0 justify-end text-muted-foreground">
                                <span class="kt-menu-item-show:hidden inline-flex"><i
                                        class="ki-filled ki-plus text-[11px]"></i></span>
                                <span class="kt-menu-item-show:inline-flex hidden"><i
                                        class="ki-filled ki-minus text-[11px]"></i></span>
                            </span>
                    </div>

                    <div
                        class="kt-menu-accordion relative gap-1 ps-[10px] before:absolute before:bottom-0 before:start-[20px] before:top-0 before:border-s before:border-border">

                        @perm('users.view')
                        <div class="kt-menu-item {{ $isUsers ? 'active' : '' }}">
                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                               href="{{ route('admin.users.index') }}" tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2"></span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Kullanıcı Listesi
                                        </span>
                            </a>
                        </div>
                        @endperm

                        @perm('roles.view')
                        <div class="kt-menu-item {{ $isRoles ? 'active' : '' }}">
                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                               href="{{ route('admin.roles.index') }}" tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2"></span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Roller
                                        </span>
                            </a>
                        </div>
                        @endperm

                        @perm('permissions.view')
                        <div class="kt-menu-item {{ $isPerms ? 'active' : '' }}">
                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                               href="{{ route('admin.permissions.index') }}" tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2"></span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            İzinler
                                        </span>
                            </a>
                        </div>
                        @endperm

                    </div>
                </div>
                @endpermAny

                {{-- Ayarlar (örnek: settings.view permission’ı ekleyince aç) --}}
                @perm('settings.view')
                <div class="kt-menu-item pt-2.25 pb-px">
                        <span
                            class="kt-menu-heading pe-[10px] ps-[10px] text-xs font-medium uppercase text-muted-foreground">
                            Ayarlar
                        </span>
                </div>

                <div class="kt-menu-item">
                    <div class="kt-menu-label gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                         tabindex="0">
                            <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                                <i class="ki-filled ki-setting-2 text-lg"></i>
                            </span>
                        <span class="kt-menu-title text-sm font-medium text-foreground">Ayarlar</span>
                    </div>
                </div>
                @endperm

            </div>
        </div>
    </div>
</div>
