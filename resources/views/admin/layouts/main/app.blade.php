<!DOCTYPE html>
<html class="h-full js-loading" lang="tr" dir="ltr"
      data-kt-theme="true"
      data-kt-theme-mode="light" >
<head>
    @include('admin.layouts.partials.head')
    @stack('admin_css')
</head>

@php
    $adminRouteName = (string) (request()->route()?->getName() ?? '');
    $adminPageMode = str_ends_with($adminRouteName, '.create') ? 'create' : 'default';
@endphp
<body
    class="antialiased flex h-full min-w-0 overflow-x-clip text-base text-foreground bg-background dash_app kt-sidebar-fixed kt-header-fixed"
    data-admin-page-mode="{{ $adminPageMode }}"
>
<div id="app-lock" class="app-lock" aria-hidden="true">
    <div class="app-lock__panel kt-card">
        <div class="flex items-center gap-3">
            <span class="app-lock-spinner"></span>
            <div class="flex flex-col">
                <div class="font-semibold text-secondary-foreground leading-none">
                    Yükleniyor…
                </div>
                <div class="text-sm text-muted-foreground">
                    Bileşenler hazırlanıyor
                </div>
            </div>
        </div>
    </div>
</div>
{{-- Tema modu init --}}
@include('admin.partials.theme-toggle')

<div class="flex min-w-0 w-full grow">
    @include('admin.layouts.main.sidebar')

    <div class="kt-wrapper flex min-w-0 w-full grow flex-col">
        @include('admin.layouts.main.header')

        <main class="min-w-0 grow pt-4 sm:pt-5" id="content" role="content">
            @hasSection('page_title')
                <div class="admin-page-titlebar mb-4 flex flex-col gap-3 px-3 sm:flex-row sm:items-center sm:justify-between sm:px-4 lg:px-6">
                    <div class="min-w-0">
                        <h1 class="text-lg font-semibold">@yield('page_title')</h1>
                        @hasSection('page_desc')
                            <div class="text-sm opacity-70 mt-1">@yield('page_desc')</div>
                        @endif
                    </div>

                    @hasSection('page_actions')
                        <div class="admin-page-actions">@yield('page_actions')</div>
                    @endif
                </div>
            @endif

            <div class="admin-content-shell px-3 sm:px-4 lg:px-6">
                @yield('content')
            </div>
        </main>

        @include('admin.layouts.main.footer')
    </div>
</div>

{{-- Media picker modal (global) --}}
@include('admin.partials.media._picker-modal')
@include('admin.partials.modals.search')

{{-- NOT: Bunu uzun vadede Vite'a taşımalısın (resources/js/admin/...) --}}
@include('admin.layouts.partials.scripts')
</body>
</html>
