@php
    $variant = $variant ?? 'feature';
    $eyebrow = $eyebrow ?? 'İlgili sayfa';
@endphp

<a href="{{ $url }}" class="home-section-route home-section-route--{{ $variant }}">
    <span class="home-section-route__copy">
        <small>{{ $eyebrow }}</small>
        <strong>{{ $label }}</strong>
    </span>
    <span class="home-section-route__arrow" aria-hidden="true">
        <svg viewBox="0 0 20 20" focusable="false">
            <path d="M4 10h12M11.5 5.5 16 10l-4.5 4.5" />
        </svg>
    </span>
</a>
