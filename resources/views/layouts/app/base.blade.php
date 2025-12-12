{{-- resources/views/layouts/demo1/base.blade.php --}}
    <!DOCTYPE html>
<html class="h-full" lang="tr" dir="ltr"
      data-kt-theme="true"
      data-kt-theme-mode="light">
<head>
    @include('layouts.partials.head')
    @stack('demo1_css')
</head>
<body class="antialiased flex h-full text-base text-foreground bg-background app kt-sidebar-fixed kt-header-fixed">
{{-- Tema modu init --}}
<script>
    (function () {
        var defaultTheme = "light";
        var mode = document.documentElement.getAttribute("data-kt-theme-mode")
            || localStorage.getItem("data-kt-theme")
            || defaultTheme;

        if (mode === "system") {
            mode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
        }

        document.documentElement.setAttribute("data-kt-theme", mode);
    })();
</script>

{{-- Page layout --}}
<div class="flex grow">
    @include('layouts.app.sidebar')

    <div class="wrapper flex grow flex-col">
        @include('layouts.app.header')

        <main id="content" role="content" class="grow content pt-5">
            @yield('content')
        </main>

        @includeWhen(View::exists('layouts.app.footer'), 'layouts.app.footer')
    </div>
</div>

@include('layouts.partials.scripts')
<script src="{{ asset('assets/js/layouts/app.js') }}"></script>
@stack('app_js')
</body>
</html>
