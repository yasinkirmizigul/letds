@php
    $siteName = ($siteSettings ?? null)?->localized('site_name') ?: config('app.name');
    $locale = $siteCurrentLocale ?? app()->getLocale();
    $isRtl = ($siteCurrentLanguage ?? null)?->is_rtl ?? false;
    $homepageContent = $homepage['content'] ?? [];
    $homepageSettings = $homepage['settings'] ?? [];
    $homepageTooltipItems = $homepage['tooltips'] ?? [];
    $homepageModes = $homepage['modes'] ?? [];
    $activeMode = collect($homepageModes)->first() ?? [
        'key' => 'analysis',
        'label' => 'İstatistiksel Analiz',
        'icon' => 'chart',
        'hero_title' => $homepageContent['hero_title'] ?? '',
        'cta_label' => $homepageContent['cta_label'] ?? '',
        'cta_url' => $homepageContent['cta_url'] ?? '#',
        'styles' => [],
    ];
    $modeList = array_values($homepageModes);
    $leftMode = $modeList[0] ?? $activeMode;
    $rightMode = $modeList[1] ?? $activeMode;
    $headerLogo = $homepage['headerLogo'] ?? null;
    $backgroundImage = $homepage['backgroundImage'] ?? null;
    $backgroundLoadingColor = $activeMode['styles']['--home-after-bg'] ?? '#ec6367';
    $homepageTitle = $homepageContent['browser_title'] ?: $siteName;
    $homeCssPath = 'assets/site/home/css/home.css';
    $homeJsPath = 'assets/site/home/js/home.js';
    $homeCssUrl = asset($homeCssPath) . '?v=' . filemtime(public_path($homeCssPath));
    $homeJsUrl = asset($homeJsPath) . '?v=' . filemtime(public_path($homeJsPath));
    $homepageStyle = collect(array_replace($activeMode['styles'] ?? [], [
        '--home-background-image' => $backgroundImage ? 'url("' . $backgroundImage['url'] . '")' : 'none',
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
    ]))->map(fn ($value, $key) => $key . ':' . $value)->implode(';');
@endphp

<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', $locale) }}"
    dir="{{ $isRtl ? 'rtl' : 'ltr' }}"
    @if($backgroundImage) class="home-background-loading" style="--home-background-loading-color: {{ $backgroundLoadingColor }}" @endif
>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $homepageTitle }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/site/images/favicon.svg') }}">
    @if($backgroundImage)
        <link rel="preload" as="image" href="{{ $backgroundImage['url'] }}" fetchpriority="high">
    @endif
    <link rel="stylesheet" href="{{ $homeCssUrl }}">
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
>
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
                    aria-label="{{ $siteName }}"
                    data-home-header-contrast="true"
                >
                    @if($headerLogo)
                        <img src="{{ $headerLogo['url'] }}" alt="{{ $headerLogo['alt'] ?: $siteName }}">
                    @else
                <svg version="1.1" id="logo-bird" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="50px" height="38px" viewBox="0 0 196 150" role="img" aria-hidden="true">
                    <path fill="#FFFFFF" d="M191.093,24.257c-11.956-.201-30.951-.723-49.513.614-1.496.108-3.137-.356-3.973-1.604-3.091-4.639-9.893-6.956-14.949-6.956-13.474,0-21.184,13.571-21.968,25.713C89.261,29.907,76.711,18.973,60.043,6.34c.227,7.467,1.176,15.347,2.322,22.71,6.621,1.708,9.882,2.667,23.345,8.411C59.604,30.438,34.16,23.823,4.344,23.498c19.222,26.09,46.251,47.769,77.883,60.469-4.309.051-10.248-2.303-13.7-3.984-11.605,16.58-19.181,36.777-19.181,58.936,0,1.594.036,3.174.108,4.742,15.991-4.18,30.425-12.236,42.221-23.104l-.005-.01c-.903-5.129-1.368-9.795-1.46-15.16-4.242.381-12.137-.373-15.708-1.744,1.264.016,5.666.041,7.183.041,2.879,0,5.702-.238,8.452-.703,24.212-4.023,42.505-25.068,42.505-50.42V41.415c0-7.307,7.927-12.926,14.052-13.504,7.064-1.156,26.869-2.719,44.43-2.843.728-.176.701-.693-.031-.811zM128.092,27.683c-1.316,0-2.39-1.068-2.39-2.384,0-1.321,1.073-2.384,2.39-2.384,1.315,0,2.384,1.063,2.384,2.384,0,1.316-1.069,2.384-2.384,2.384z"/>
                </svg>
                    @endif
                </a>

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
            </nav>
        </div>
    </header>

    <main>
        <section id="before-after" class="home-before-after" aria-label="Before and after presentation">
            <div class="view view-after" data-after-view>
                <div class="wrapper-after">
                    <div class="img-bird-wrapper et-in-viewport-check" et-anim="floating_special" et-anim-duration="3500" et-anim-delay="0" et-anim-easing="ease">
                        <img src="{{ asset('assets/site/home/images/concept-before.svg') }}" alt="">
                        @foreach($homepageTooltipItems as $item)
                            <button type="button" class="tooltip-item tooltip-item-{{ $item['position'] }}" data-header-text="{{ $item['key'] }}" aria-label="{{ $item['aria_label'] }}"></button>
                        @endforeach
                    </div>

                    <div class="shadown-bird et-in-viewport-check" et-anim="pulse_special" et-anim-duration="3500" et-anim-delay="0" et-anim-easing="ease"></div>

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
                    <div class="img-bird-wrapper et-in-viewport-check" et-anim="floating_special" et-anim-duration="3500" et-anim-delay="0" et-anim-easing="ease">
                        <img src="{{ asset('assets/site/home/images/concept-after.svg') }}" alt="">
                        @foreach($homepageTooltipItems as $item)
                            <button type="button" class="tooltip-item tooltip-item-{{ $item['position'] }}" data-header-text="{{ $item['key'] }}" aria-label="{{ $item['aria_label'] }}"></button>
                        @endforeach
                    </div>

                    <div class="shadown-bird et-in-viewport-check" et-anim="pulse_special" et-anim-duration="3500" et-anim-delay="0" et-anim-easing="ease"></div>

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
                <span class="icon-drag" aria-hidden="true"></span>
            </button>
        </section>
    </main>

    <script defer src="{{ $homeJsUrl }}"></script>
</body>
</html>
