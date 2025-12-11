<!DOCTYPE html>
<html lang="tr">
<head>
    {{-- Head --}}
    @includeIf('layouts.partials.head')
</head>
<body id="kt_body" class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled">

<div class="d-flex flex-column flex-root" id="kt_app_root">
    <div class="page d-flex flex-row flex-column-fluid">

        {{-- Aside --}}
        @includeIf('partials.aside')

        <div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">

            {{-- Header --}}
            @includeIf('partials.header')

            {{-- Content --}}
            <div class="content d-flex flex-column flex-column-fluid" id="kt_content">
                @yield('content')
            </div>

            {{-- Footer --}}
            @includeIf('partials.footer')

        </div>
    </div>
</div>

{{-- Sripts --}}
@includeIf('layouts.partials.scripts')

</body>
</html>
