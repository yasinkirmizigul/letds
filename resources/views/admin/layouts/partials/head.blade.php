<title>{{ $pageTitle ?? 'Yönetim Paneli' }} | Yönetim Paneli</title>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="csrf-token" content="{{ csrf_token() }}">
{{-- SEO istersen burayı doldurursun --}}
<meta name="description" content="{{ $pageDescription ?? 'Yönetim Paneli' }}" />

{{-- Fonts --}}
<link rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" />

{{-- Vendor CSS --}}
@stack('vendor_css')

{{-- Theme global CSS --}}
<link rel="stylesheet" href="{{ asset('assets/vendors/apexcharts/apexcharts.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendors/keenicons/styles.bundle.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}" />

{{-- İstersen sayfa özel CSS buraya --}}
@stack('custom_css')
