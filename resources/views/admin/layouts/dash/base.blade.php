{{-- resources/views/layouts/demo1/base.blade.php --}}
    <!DOCTYPE html>
<html class="h-full" lang="tr" dir="ltr"
      data-kt-theme="true"
      data-kt-theme-mode="light">
<head>
    @include('admin.layouts.partials.head')
    @stack('app_css')
</head>
<body class="antialiased flex h-full text-base text-foreground bg-background app kt-sidebar-fixed kt-header-fixed">
{{-- Tema modu init --}}
@include('admin.partials.theme-toggle')
{{-- Page layout --}}
<div class="flex grow">
    @include('admin.layouts.dash.sidebar')

    <div class="kt-wrapper flex grow flex-col">
        @include('admin.layouts.dash.header')

        <main class="grow pt-5" id="content" role="content">
            @yield('content')
        </main>

        @includeWhen(View::exists('admin.layouts.dash.footer'), 'admin.layouts.dash.footer')
    </div>
</div>

@include('admin.layouts.partials.scripts')
<script src="{{ asset('assets/js/layouts/dash.js') }}"></script>
@stack('app_js')
</body>
</html>
