{{-- resources/views/layouts/demo1/base.blade.php --}}
        <!DOCTYPE html>
<html class="h-full" lang="tr" dir="ltr"
      data-kt-theme="true"
      data-kt-theme-mode="light">
<head>
    @include('app.layouts.partials.head')
    @stack('app_css')
</head>
<body class="antialiased flex h-full text-base text-foreground bg-background app kt-sidebar-fixed kt-header-fixed">
{{-- Tema modu init --}}
@include('app.partials.theme-toggle')
{{-- Page layout --}}
<div class="flex grow">
    @include('app.layouts.app.sidebar')

    <div class="kt-wrapper flex grow flex-col">
        @include('app.layouts.app.header')

        <main class="grow pt-5" id="content" role="content">
            @yield('content')
        </main>

        @includeWhen(View::exists('app.layouts.app.footer'), 'app.layouts.app.footer')
    </div>
</div>

@include('app.layouts.partials.scripts')
<script src="{{ asset('assets/js/layouts/app.js') }}"></script>
@stack('app_js')
</body>
</html>
