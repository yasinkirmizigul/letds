<!-- Header -->
<header class="kt-header fixed end-0 start-0 top-0 z-10 flex shrink-0 items-stretch bg-background" data-kt-sticky="true"
    data-kt-sticky-class="border-b border-border" data-kt-sticky-name="header" id="header">
    <!-- Container -->
    <div class="flex items-stretch justify-end kt-container-fixed lg:gap-2 max-w-[90%]" id="headerContainer">
        <!-- Mobile Logo -->
        <div class="-ms-1 flex items-center gap-2.5 lg:hidden">
            {{--<a class="shrink-0" href="#">
                <img class="max-h-[25px] w-full" src="assets/media/app/mini-logo.svg" />
            </a>--}}
            <div class="flex items-center">
                <button class="kt-btn kt-btn-icon kt-btn-light" data-kt-drawer-toggle="#sidebar">
                    <i class="ki-filled ki-menu">
                    </i>
                </button>
            </div>
        </div>
        <!-- Topbar -->
        <div class="flex items-center gap-2.5">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="kt-btn kt-btn-ghost kt-btn-icon size-8 hover:bg-background hover:[&_i]:text-primary">
                    <i class="ki-outline ki-exit-right text-xl"></i>
                </button>
            </form>
            @include('admin.partials.topbar-user-dropdown')
        </div>
        <!-- End of Topbar -->
    </div>
    <!-- End of Container -->
</header>
<!-- End of Header -->
