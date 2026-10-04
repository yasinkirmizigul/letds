{{-- Global JS bundle --}}
<script src="{{ asset('assets/admin/js/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('assets/admin/js/core.bundle.js') }}"></script>
<script src="{{ asset('assets/admin/vendors/ktui/ktui.min.js') }}"></script>
<script
    src="https://cdn.jsdelivr.net/gh/yasinkirmizigul/letds@78ed8455ae58351e82cc6471afc8181785870eb3/public/assets/admin/plugins/global/plugins.bundle.js"
    integrity="sha384-0XhE5yWVZ06NM+Ne65TKqFljEnvP0CxyX+wM1nrNw/94047GRXFhy1QK9ruhjw5M"
    crossorigin="anonymous"
></script>
<script src="{{ asset('assets/admin/plugins/custom/vis-timeline/vis-timeline.bundle.js') }}"></script>
<script src="{{ asset('assets/admin/js/select2.min.js') }}"></script>
<script src="{{ asset('assets/admin/js/datatables.min.js') }}"></script>
<script src="{{ asset('assets/admin/vendors/apexcharts/apexcharts.min.js') }}"></script>

{{-- Vendor stacks --}}
@stack('vendor_js')

{{-- Vite --}}
@vite(['resources/js/admin/app.js'])

{{-- Page stacks --}}
@stack('admin_js')
@stack('custom_js')
@stack('page_js')
