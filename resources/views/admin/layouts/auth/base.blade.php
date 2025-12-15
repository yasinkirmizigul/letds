{{-- resources/views/layouts/demo1/app.blade.php --}}
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
@yield('content')

@include('admin.layouts.partials.scripts')
<script src="{{ asset('assets/js/layouts/main.js') }}"></script>
@stack('app_js')
</body>
</html>
