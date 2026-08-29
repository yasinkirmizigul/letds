@php
    $modeStyle = static fn (array $mode): string => collect($mode['styles'] ?? [])
        ->map(fn ($value, $key) => $key . ':' . $value)
        ->implode(';');
@endphp

<section class="home-probablue-hero" aria-label="Probablue hizmet alanları">
    <header class="home-probablue-hero__header">
        <a
            href="{{ \App\Support\Site\SiteLocalization::homeUrl($locale) }}"
            class="home-probablue-hero__brand {{ $headerLogo ? 'has-image' : 'is-fallback' }}"
            aria-label="{{ $siteName }}"
        >
            @if($headerLogo)
                <img src="{{ $headerLogo['url'] }}" alt="{{ $headerLogo['alt'] ?: $siteName }}">
            @else
                <span class="probablue-brand probablue-brand--home" aria-hidden="true">
                    <span class="probablue-brand__name">PROBA<span>BLUE</span></span>
                    <span class="probablue-brand__tagline">İstatistiksel Analiz ve Danışma</span>
                </span>
            @endif
        </a>

        <div class="home-probablue-hero__theme">
            @include('site.partials.theme-toggle', ['variant' => 'home'])
            @include('site.partials.home-navigation-menu')
        </div>
    </header>

    <div class="home-probablue-hero__panels">
        <article class="home-probablue-panel home-probablue-panel--analysis" style="{{ $modeStyle($leftMode) }}">
            <span class="home-probablue-panel__orb" aria-hidden="true"></span>
            <div class="home-probablue-panel__content">
                <span class="home-probablue-panel__eyebrow">{{ $leftMode['label'] }}</span>
                <h1>{{ $leftMode['hero_title'] }}</h1>
                <a
                    class="home-probablue-panel__cta"
                    href="{{ $leftMode['cta_url'] }}"
                    title="{{ $leftMode['cta_label'] }}"
                    @if($ctaNewTab) target="_blank" rel="noopener" @endif
                >
                    <span>{{ $leftMode['cta_label'] }}</span>
                    <span aria-hidden="true">&#8599;</span>
                </a>
            </div>
        </article>

        <article class="home-probablue-panel home-probablue-panel--consultation" style="{{ $modeStyle($rightMode) }}">
            <span class="home-probablue-panel__grid" aria-hidden="true"></span>
            <div class="home-probablue-panel__content">
                <span class="home-probablue-panel__eyebrow">{{ $rightMode['label'] }}</span>
                <h2>{{ $rightMode['hero_title'] }}</h2>
                <a
                    class="home-probablue-panel__cta"
                    href="{{ $rightMode['cta_url'] }}"
                    title="{{ $rightMode['cta_label'] }}"
                    @if($ctaNewTab) target="_blank" rel="noopener" @endif
                >
                    <span>{{ $rightMode['cta_label'] }}</span>
                    <span aria-hidden="true">&#8599;</span>
                </a>
            </div>
        </article>

        <div class="home-probablue-hero__device" aria-hidden="true">
            <span class="home-probablue-hero__device-glow"></span>
            @include('site.partials.home-hero-computer', ['variant' => 'pv', 'idPrefix' => 'probablue-pv'])
            <span class="home-probablue-hero__device-shadow"></span>
        </div>
    </div>

    <div class="home-probablue-hero__scroll" aria-hidden="true">
        <span></span>
        Keşfet
    </div>
</section>
