<title>{{ $pageTitle ?? 'Yönetim Paneli' }} | Yönetim Paneli</title>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="csrf-token" content="{{ csrf_token() }}">
{{-- SEO istersen burayı doldurursun --}}
<meta name="description" content="{{ $pageDescription ?? 'Yönetim Paneli' }}" />
<link rel="icon" type="image/svg+xml" href="{{ asset('assets/admin/media/app/favicon.svg') }}">
{{-- Vendor CSS --}}
@stack('vendor_css')

{{-- Theme global CSS --}}
<link rel="stylesheet" href="{{ asset('assets/admin/plugins/custom/vis-timeline/vis-timeline.bundle.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/admin/plugins/global/plugins.bundle.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/admin/vendors/apexcharts/apexcharts.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/admin/vendors/keenicons/styles.bundle.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/admin/css/select2.min.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/admin/css/datatables.min.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/admin/css/styles.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/admin/css/custom.css') }}" />
@vite(['resources/css/app.css'])

{{-- İstersen sayfa özel CSS buraya --}}
@stack('custom_css')
@stack('page_css')
