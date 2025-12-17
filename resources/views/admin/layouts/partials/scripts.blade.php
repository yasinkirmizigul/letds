{{-- Global JS bundle --}}
<script src="{{ asset('assets/js/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('assets/js/core.bundle.js') }}"></script>
<script src="{{ asset('assets/js/select2.min.js') }}"></script>
<script src="{{ asset('assets/js/datatables.min.js') }}"></script>
<script src="{{ asset('assets/vendors/ktui/ktui.min.js') }}"></script>
<script src="{{ asset('assets/vendors/apexcharts/apexcharts.min.js') }}"></script>

{{-- Custom JS stack’leri --}}
@stack('custom_js')
@stack('vendor_js')
@stack('page_js')

{{-- Senin uygulama JS’in (Vite) --}}
@vite(['resources/js/app.js'])
