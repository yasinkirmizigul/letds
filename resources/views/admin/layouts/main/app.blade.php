<!DOCTYPE html>
<html class="h-full" lang="tr" dir="ltr"
      data-kt-theme="true"
      data-kt-theme-mode="light">
<head>
    @include('admin.layouts.partials.head')
    @stack('admin_css')
</head>

<body class="antialiased flex h-full text-base text-foreground bg-background app kt-sidebar-fixed kt-header-fixed">
{{-- Tema modu init --}}
@include('admin.partials.theme-toggle')

<div class="flex grow">
    @include('admin.layouts.main.sidebar')

    <div class="kt-wrapper flex grow flex-col">
        @include('admin.layouts.main.header')

        <main class="grow pt-5" id="content" role="content">
            @hasSection('page_title')
                <div class="px-6 mb-4 flex items-center justify-between">
                    <div>
                        <h1 class="text-lg font-semibold">@yield('page_title')</h1>
                        @hasSection('page_desc')
                            <div class="text-sm opacity-70 mt-1">@yield('page_desc')</div>
                        @endif
                    </div>

                    @hasSection('page_actions')
                        <div>@yield('page_actions')</div>
                    @endif
                </div>
            @endif

            <div class="px-6">
                @yield('content')
            </div>
        </main>

        @include('admin.layouts.main.footer')
    </div>
</div>

@include('admin.layouts.partials.scripts')

{{-- NOT: Bunu uzun vadede Vite'a taşımalısın (resources/js/admin/...) --}}
<script src="{{ asset('assets/js/layouts/main.js') }}"></script>

@stack('admin_js')
@stack('page_js')
</body>
</html>
