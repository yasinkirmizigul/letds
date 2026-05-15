{{-- Global JS bundle --}}
<script src="{{ asset('assets/js/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('assets/js/core.bundle.js') }}"></script>
<script src="{{ asset('assets/vendors/ktui/ktui.min.js') }}"></script>
<script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
<script src="{{ asset('assets/plugins/custom/vis-timeline/vis-timeline.bundle.js') }}"></script>
<script src="{{ asset('assets/js/select2.min.js') }}"></script>
<script src="{{ asset('assets/js/datatables.min.js') }}"></script>
<script src="{{ asset('assets/vendors/apexcharts/apexcharts.min.js') }}"></script>

{{-- Vendor stacks --}}
@stack('vendor_js')

{{-- Vite --}}
@vite(['resources/js/admin/app.js'])

{{-- Page stacks --}}
@stack('admin_js')
@stack('custom_js')
@stack('page_js')
