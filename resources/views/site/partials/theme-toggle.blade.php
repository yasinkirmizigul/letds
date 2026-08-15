@php
    $themeToggleClass = trim('site-theme-toggle ' . (($variant ?? 'site') === 'home' ? 'home-theme-toggle' : 'site-theme-toggle--shell'));
@endphp

<button
    type="button"
    class="{{ $themeToggleClass }}"
    data-site-theme-toggle
    aria-label="Koyu temaya geç"
    aria-pressed="false"
    title="Koyu temaya geç"
    @if(($variant ?? 'site') === 'home') data-home-header-contrast="true" @endif
>
    <svg class="site-theme-toggle__icon site-theme-toggle__icon--moon" data-site-theme-icon="moon" viewBox="0 0 24 24" aria-hidden="true">
        <path d="M20.25 15.05A8.5 8.5 0 0 1 8.95 3.75 8.5 8.5 0 1 0 20.25 15.05Z" />
    </svg>
    <svg class="site-theme-toggle__icon site-theme-toggle__icon--sun" data-site-theme-icon="sun" viewBox="0 0 24 24" aria-hidden="true">
        <circle cx="12" cy="12" r="3.75" />
        <path d="M12 2.25v2M12 19.75v2M21.75 12h-2M4.25 12h-2M18.9 5.1l-1.42 1.42M6.52 17.48 5.1 18.9M18.9 18.9l-1.42-1.42M6.52 6.52 5.1 5.1" />
    </svg>
</button>
