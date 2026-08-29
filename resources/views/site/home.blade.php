@php
    $siteName = trim((string) (($siteSettings ?? null)?->localized('site_name') ?: 'PROBABLUE'));
    $siteName = strcasecmp($siteName, 'Laravel') === 0 ? 'PROBABLUE' : $siteName;
    $locale = $siteCurrentLocale ?? app()->getLocale();
    $isRtl = ($siteCurrentLanguage ?? null)?->is_rtl ?? false;
    $homepageContent = $homepage['content'] ?? [];
    $homepageSettings = $homepage['settings'] ?? [];
    $heroLayout = in_array(($homepageSettings['hero_layout'] ?? 'interactive'), ['interactive', 'probablue'], true)
        ? $homepageSettings['hero_layout']
        : 'interactive';
    $homepageTooltipItems = $homepage['tooltips'] ?? [];
    $sitePalette = ($siteSettings ?? null)?->palette() ?? 'coral';
    $homepagePaletteStyles = ($siteSettings ?? null)?->homepagePaletteStyles() ?? [];
    $homepageModes = collect($homepage['modes'] ?? [])
        ->map(function (array $mode) use ($homepagePaletteStyles): array {
            $mode['styles'] = array_replace($mode['styles'] ?? [], $homepagePaletteStyles);

            return $mode;
        })
        ->all();
    $homepageSections = $homepage['sections'] ?? [];
    $activeMode = collect($homepageModes)->first() ?? [
        'key' => 'analysis',
        'label' => 'İstatistiksel Analiz',
        'icon' => 'chart',
        'hero_title' => $homepageContent['hero_title'] ?? '',
        'cta_label' => $homepageContent['cta_label'] ?? '',
        'cta_url' => $homepageContent['cta_url'] ?? '#',
        'styles' => $homepagePaletteStyles,
    ];
    $modeList = array_values($homepageModes);
    $leftMode = $modeList[0] ?? $activeMode;
    $rightMode = $modeList[1] ?? $activeMode;
    $headerLogo = $homepage['headerLogo'] ?? null;
    $backgroundImage = $homepage['backgroundImage'] ?? null;
    $backgroundDefaults = $homepage['backgroundDefaults'] ?? [];
    $backgroundLightUrl = $backgroundImage['url'] ?? data_get($backgroundDefaults, 'light.url');
    $backgroundDarkUrl = $backgroundImage['url'] ?? data_get($backgroundDefaults, 'dark.url', $backgroundLightUrl);
    $backgroundLoadingColor = $activeMode['styles']['--home-after-bg'] ?? '#ec6367';
    $homepageTitle = $homepageContent['browser_title'] ?: $siteName;
    $homeCssPath = 'assets/site/home/css/home.css';
    $homeJsPath = 'assets/site/home/js/home.js';
    $homeCssUrl = asset($homeCssPath) . '?v=' . filemtime(public_path($homeCssPath));
    $homeJsUrl = asset($homeJsPath) . '?v=' . filemtime(public_path($homeJsPath));
    $siteMember = auth('member')->user();
    $hasActiveMemberSession = $siteMember && $siteMember->is_active && !$siteMember->trashed();
    $homepageStyle = collect(array_replace($activeMode['styles'] ?? [], [
        '--home-background-image' => $backgroundLightUrl ? 'url("' . $backgroundLightUrl . '")' : 'none',
        '--home-background-image-dark' => $backgroundDarkUrl ? 'url("' . $backgroundDarkUrl . '")' : 'var(--home-background-image)',
        '--home-background-brightness' => (float) $homepageSettings['background_brightness'] . '%',
        '--home-background-overlay-opacity' => $homepageSettings['background_overlay_enabled']
            ? (float) $homepageSettings['background_overlay_opacity'] / 100
            : 0,
        '--home-background-position' => $homepageSettings['background_position'],
        '--home-analysis-tab-after-text' => $homepageSettings['analysis_tab_after_text_color'],
        '--home-analysis-tab-before-text' => $homepageSettings['analysis_tab_before_text_color'],
        '--home-consultation-tab-after-text' => $homepageSettings['consultation_tab_after_text_color'],
        '--home-consultation-tab-before-text' => $homepageSettings['consultation_tab_before_text_color'],
        '--home-logo' => $homepageSettings['logo_color'],
        '--home-sticky-header-bg' => $homepageSettings['sticky_header_background'],
        '--home-sticky-logo' => $homepageSettings['sticky_logo_color'],
    ], $homepagePaletteStyles))->map(fn ($value, $key) => $key . ':' . $value)->implode(';');
@endphp

<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', $locale) }}"
    dir="{{ $isRtl ? 'rtl' : 'ltr' }}"
    class="home-hero-pending{{ $backgroundImage ? ' home-background-loading' : '' }}"
    @if($backgroundImage) style="--home-background-loading-color: {{ $backgroundLoadingColor }}" @endif
>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('site.partials.theme-bootstrap')
    <title>{{ $homepageTitle }}</title>
    @include('partials.favicons', [
        'faviconSvgPath' => 'assets/site/images/favicon.svg',
        'faviconPngPath' => 'assets/site/images/favicon-32x32.png',
    ])
    @if($backgroundImage)
        <link rel="preload" as="image" href="{{ $backgroundImage['url'] }}" fetchpriority="high">
    @endif
    <link rel="stylesheet" href="{{ $homeCssUrl }}">
    @vite(['resources/css/title-tooltips.css', 'resources/js/site/title-tooltips.js'])
    <noscript>
        <style>
            html.home-hero-pending .view-after { width: 50% !important; }
            html.home-hero-pending .home-drag-handle { left: 50% !important; }
        </style>
    </noscript>
    @if($backgroundImage)
        <noscript><style>html.home-background-loading body.site-home-index { opacity: 1 !important; }</style></noscript>
    @endif
</head>
<body
    class="site-home-index"
    style="{{ $homepageStyle }}"
    data-stat-symbols="{{ $homepageSettings['cursor_symbols_enabled'] ? 'true' : 'false' }}"
    data-stat-symbol-mode="{{ $homepageSettings['cursor_symbol_mode'] }}"
    data-home-mode="{{ $activeMode['key'] }}"
    data-home-background-url="{{ $backgroundImage['url'] ?? '' }}"
    data-home-layout="{{ $heroLayout }}"
    data-site-palette="{{ $sitePalette }}"
>
    @if($heroLayout === 'interactive')
    <header id="header-wrapper" class="site-home-header">
        <div class="home-container">
            <nav class="home-mode-nav" aria-label="Ana sayfa hizmetleri">
                <button
                    type="button"
                    class="home-mode-tab home-mode-tab--left is-active"
                    role="tab"
                    aria-selected="true"
                    data-home-mode-tab="{{ $leftMode['key'] }}"
                    data-home-mode-payload="{{ json_encode($leftMode, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}"
                    data-home-header-contrast="true"
                >
                    <span class="home-mode-tab__icon">@include('site.partials.home-mode-icon', ['icon' => $leftMode['icon']])</span>
                    <span>{{ $leftMode['label'] }}</span>
                </button>

                <a
                    href="{{ \App\Support\Site\SiteLocalization::homeUrl($locale) }}"
                    class="wrapper-logo home-header-logo {{ $headerLogo ? 'has-image' : 'is-fallback' }}"
                    aria-label="{{ $headerLogo ? $siteName : 'PROBABLUE - İstatistiksel Analiz ve Danışma' }}"
                    data-home-header-contrast="true"
                >
                    @if($headerLogo)
                        <img src="{{ $headerLogo['url'] }}" alt="{{ $headerLogo['alt'] ?: $siteName }}">
                    @else
                        <span class="home-split-brand" data-home-split-brand="true" aria-hidden="true">
                            <span class="probablue-brand probablue-brand--home home-split-brand__layer home-split-brand__layer--after">
                                <span class="probablue-brand__mark" data-home-brand-mark="true"></span>
                                <span class="probablue-brand__name">PROBA<span>BLUE</span></span>
                                <span class="probablue-brand__tagline">İstatistiksel Analiz ve Danışma</span>
                            </span>
                            <span class="probablue-brand probablue-brand--home home-split-brand__layer home-split-brand__layer--before">
                                <span class="probablue-brand__mark" data-home-brand-mark="true"></span>
                                <span class="probablue-brand__name">PROBA<span>BLUE</span></span>
                                <span class="probablue-brand__tagline">İstatistiksel Analiz ve Danışma</span>
                            </span>
                        </span>
                    @endif
                </a>

                <div class="home-mode-nav__end">
                    <button
                        type="button"
                        class="home-mode-tab home-mode-tab--right"
                        role="tab"
                        aria-selected="false"
                        data-home-mode-tab="{{ $rightMode['key'] }}"
                        data-home-mode-payload="{{ json_encode($rightMode, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}"
                        data-home-header-contrast="true"
                    >
                        <span>{{ $rightMode['label'] }}</span>
                        <span class="home-mode-tab__icon">@include('site.partials.home-mode-icon', ['icon' => $rightMode['icon']])</span>
                    </button>

                    @include('site.partials.theme-toggle', ['variant' => 'home'])
                    @include('site.partials.home-navigation-menu')
                </div>
            </nav>
        </div>
    </header>
    @endif

    <main>
        @if($heroLayout === 'interactive')
        <section id="before-after" class="home-before-after" aria-label="Before and after presentation">
            <div class="view view-after" data-after-view>
                <div class="wrapper-after">
                    <span class="home-surface-pattern" aria-hidden="true"></span>
                    <div class="img-bird-wrapper home-hero-float">
                        @include('site.partials.home-hero-computer', ['variant' => 'pvt'])
                        @foreach($homepageTooltipItems as $item)
                            <button type="button" class="tooltip-item tooltip-item-{{ $item['position'] }}" data-header-text="{{ $item['key'] }}" aria-label="{{ $item['aria_label'] }}"></button>
                        @endforeach
                    </div>

                    <div class="shadown-bird home-hero-shadow"></div>

                    <div class="content-before-after right-position">
                        <div class="content-right">
                            <h1 data-home-hero-title="true">{{ $activeMode['hero_title'] }}</h1>
                            <p class="text-center">
                                <a
                                    href="{{ $activeMode['cta_url'] }}"
                                    class="btn-sumary btn-sumary-big btn-header"
                                    title="{{ $activeMode['cta_label'] }}"
                                    data-home-cta="true"
                                    @if($homepageSettings['cta_new_tab']) target="_blank" rel="noopener" @endif
                                >
                                    <span data-home-cta-label="true">{{ $activeMode['cta_label'] }}</span>
                                    <span class="home-cta-arrow" aria-hidden="true">›</span>
                                </a>
                            </p>
                        </div>

                        <div class="content-left">
                            <ul>
                                @foreach($homepageTooltipItems as $item)
                                    <li class="{{ $item['key'] }} {{ $loop->first ? 'active' : '' }}">
                                        <div class="content-detail-wrapper">
                                            <div class="img-position">
                                                <img src="{{ asset('assets/site/home/images/tooltip-dot.png') }}" alt="">
                                            </div>
                                            <h2 style="--home-tooltip-text: {{ $item['title_color'] }}">{!! \App\Support\Security\HtmlSanitizer::sanitize($item['title']) !!}</h2>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="view view-before">
                <div class="wrapper-before">
                    <span class="home-surface-pattern" aria-hidden="true"></span>
                    <div class="img-bird-wrapper home-hero-float">
                        @include('site.partials.home-hero-computer', ['variant' => 'pv', 'idPrefix' => 'interactive-pv'])
                        @foreach($homepageTooltipItems as $item)
                            <button type="button" class="tooltip-item tooltip-item-{{ $item['position'] }}" data-header-text="{{ $item['key'] }}" aria-label="{{ $item['aria_label'] }}"></button>
                        @endforeach
                    </div>

                    <div class="shadown-bird home-hero-shadow"></div>

                    <div class="content-before-after left-position">
                        <div class="content-right">
                            <h1 data-home-hero-title="true">{{ $activeMode['hero_title'] }}</h1>
                            <p class="text-center">
                                <a
                                    href="{{ $activeMode['cta_url'] }}"
                                    class="btn-sumary btn-sumary-big btn-header"
                                    title="{{ $activeMode['cta_label'] }}"
                                    data-home-cta="true"
                                    @if($homepageSettings['cta_new_tab']) target="_blank" rel="noopener" @endif
                                >
                                    <span data-home-cta-label="true">{{ $activeMode['cta_label'] }}</span>
                                    <span class="home-cta-arrow" aria-hidden="true">›</span>
                                </a>
                            </p>
                        </div>

                        <div class="content-left">
                            <ul>
                                @foreach($homepageTooltipItems as $item)
                                    <li class="{{ $item['key'] }} {{ $loop->first ? 'active' : '' }}">
                                        <div class="content-detail-wrapper">
                                            <div class="img-position">
                                                <img src="{{ asset('assets/site/home/images/tooltip-dot.png') }}" alt="">
                                            </div>
                                            <h2 style="--home-tooltip-text: {{ $item['highlighted_title_color'] }}">{!! \App\Support\Security\HtmlSanitizer::sanitize($item['highlighted_title']) !!}</h2>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" id="dragme" class="home-drag-handle" aria-label="Compare views" aria-orientation="vertical" aria-valuemin="0" aria-valuemax="100" aria-valuenow="50">
                <span class="icon-drag" aria-hidden="true">
                    <svg class="icon-drag__arrow" viewBox="0 0 16 8" focusable="false">
                        <path d="M0 4 4 1v2h8V1l4 3-4 3V5H4v2Z" />
                    </svg>
                </span>
            </button>
        </section>
        @else
            @include('site.home-sections.probablue-hero', [
                'leftMode' => $leftMode,
                'rightMode' => $rightMode,
                'headerLogo' => $headerLogo,
                'siteName' => $siteName,
                'locale' => $locale,
                'ctaNewTab' => $homepageSettings['cta_new_tab'],
            ])
        @endif

        @foreach($homepageSections as $section)
            @if(($section['type'] ?? null) === 'features')
                @include('site.home-sections.features', ['section' => $section])
            @endif
        @endforeach

        @include('site.home-sections.about-faq')
    </main>

    <script defer src="{{ $homeJsUrl }}"></script>
</body>
</html>
