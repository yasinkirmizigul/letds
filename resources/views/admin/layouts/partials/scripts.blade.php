{{-- Global JS bundle --}}
<script defer src="{{ asset('assets/js/jquery-3.7.1.min.js') }}"></script>
<script defer src="{{ asset('assets/js/core.bundle.js') }}"></script>
<script defer src="{{ asset('assets/vendors/ktui/ktui.min.js') }}"></script>

<script defer src="{{ asset('assets/js/select2.min.js') }}"></script>
<script defer src="{{ asset('assets/js/datatables.min.js') }}"></script>
<script defer src="{{ asset('assets/vendors/apexcharts/apexcharts.min.js') }}"></script>

{{-- Vite --}}
@vite(['resources/js/app.js'])

{{-- stacks --}}
@stack('admin_js')
@stack('custom_js')
@stack('vendor_js')
@stack('page_js')
