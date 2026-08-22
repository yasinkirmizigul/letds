@php
    $requestedVariant = (string) ($variant ?? 'pv');
    $computerVariant = in_array($requestedVariant, ['outline', 'pvt'], true) ? 'pvt' : 'pv';
    $svgIdPrefix = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($idPrefix ?? 'home-pv')) ?: 'home-pv';
@endphp

@if($computerVariant === 'pvt')
    <img
        class="home-hero-computer home-hero-computer--pvt"
        src="{{ asset('assets/site/home/images/p-pvt.svg') }}"
        alt=""
        aria-hidden="true"
        decoding="async"
        fetchpriority="high"
        data-home-computer-variant="pvt"
    >
@else
    @include('site.partials.home-pv-graphic', ['svgIdPrefix' => $svgIdPrefix])
@endif
