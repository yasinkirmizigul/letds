@php
    $publicSiteName = trim((string) $siteSettings->localized('site_name'));
    $publicSiteName = $publicSiteName === '' || strcasecmp($publicSiteName, 'Laravel') === 0 ? 'PROBABLUE' : $publicSiteName;
    $publicSiteTagline = trim((string) $siteSettings->localized('site_tagline'));
    $publicSiteTagline = $publicSiteTagline === '' || $publicSiteTagline === 'Dijital vitrin ve içerik yönetimi'
        ? 'İstatistiksel Analiz ve Danışma'
        : $publicSiteTagline;
@endphp

<!DOCTYPE html>
<html lang="{{ $siteCurrentLocale }}" dir="{{ $siteCurrentLanguage?->is_rtl ? 'rtl' : 'ltr' }}" data-kt-theme="true">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- JS erken işareti: reveal animasyonlarının gizli başlangıç durumu yalnızca JS çalışırken uygulanır (script yüklenmezse içerik görünür kalır) --}}
    <script>document.documentElement.classList.add('site-js');</script>
    @include('site.partials.theme-bootstrap')
    <title>{{ ($pageTitle ?? null) ? $pageTitle . ' | ' . $publicSiteName : $publicSiteName }}</title>
    @if(filled($metaDescription ?? null))
        <meta name="description" content="{{ $metaDescription }}">
        <meta property="og:description" content="{{ $metaDescription }}">
    @endif
    <meta property="og:title" content="{{ ($pageTitle ?? null) ?: $publicSiteName }}">
    <meta property="og:type" content="{{ $openGraphType ?? 'website' }}">
    <meta property="og:url" content="{{ $canonicalUrl ?? request()->url() }}">
    <link rel="canonical" href="{{ $canonicalUrl ?? request()->url() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/site/images/favicon.svg') }}">

    @stack('site_meta')
    @stack('site_vendor_css')
    <script defer src="{{ asset('assets/site/js/core.bundle.js') }}"></script>
    <script defer src="{{ asset('assets/site/vendors/ktui/ktui.min.js') }}"></script>
    @vite(['resources/css/app.css', 'resources/js/site/app.js'])
    @stack('site_css')
</head>
<body
    class="site-shell min-h-screen bg-background text-foreground"
    data-site-palette="{{ $siteSettings->palette() }}"
    style="{{ $siteSettings->paletteCssVariables() }}"
>
@php
    $siteMember = auth('member')->user();
    $hasActiveMemberSession = $siteMember && $siteMember->is_active && !$siteMember->trashed();
    $hasFooterNavigation = $siteFooterNavigation->isNotEmpty();
@endphp
<div class="min-h-screen">
    @if($siteSettings->under_construction_enabled)
        <div class="border-b border-border bg-warning/10">
            <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-3 text-sm md:flex-row md:items-center md:justify-between">
                <div class="font-medium text-foreground">
                    {{ $siteSettings->localized('under_construction_title') ?: 'Yapım aşaması bildirimi' }}
                </div>
                <div class="text-muted-foreground">
                    {{ $siteSettings->localized('under_construction_message') ?: 'Bu alan geçici bilgilendirme için aktif.' }}
                </div>
            </div>
        </div>
    @endif

    <a href="#site-main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 kt-btn kt-btn-primary">İçeriğe atla</a>

    <header class="site-header sticky top-0 z-40 border-b border-border bg-background/90 backdrop-blur-xl" x-data="{ mobileOpen: false }">
        <div class="site-header__inner mx-auto flex h-16 items-center justify-between gap-2 px-3 sm:gap-4 sm:px-4 lg:h-[72px] lg:px-6">
            <a href="{{ \App\Support\Site\SiteLocalization::homeUrl($siteCurrentLocale) }}" class="probablue-brand probablue-brand--shell" aria-label="PROBABLUE - İstatistiksel Analiz ve Danışma">
                <span class="probablue-brand__name">PROBA<span>BLUE</span></span>
                <span class="probablue-brand__tagline">İstatistiksel Analiz ve Danışma</span>
            </a>

            <nav class="site-desktop-navigation hidden items-center xl:flex" aria-label="Ana menü" data-site-primary-navigation>
                @foreach($sitePrimaryNavigation as $navItem)
                    @include('site.partials.navigation.desktop-item', ['navItem' => $navItem])
                @endforeach
            </nav>

            <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                @include('site.partials.theme-toggle')

                @if($siteLanguages->count() > 1)
                    <div x-data="{ open: false }" @click.outside="open = false" class="relative">
                        <button type="button" @click="open = !open" :aria-expanded="open" aria-haspopup="true" class="site-language-trigger kt-btn kt-btn-light">
                            {{ $siteCurrentLanguage?->native_name ?: strtoupper($siteCurrentLocale) }}
                        </button>
                        <div x-show="open" x-cloak x-transition.origin.top class="absolute right-0 top-full z-50 mt-2 min-w-[180px] rounded-2xl border border-border bg-background p-2 shadow-lg">
                            @foreach($siteLanguages as $language)
                                <a href="{{ \App\Support\Site\SiteLocalization::switchUrl(request(), $language->code, $currentSitePage ?? null) }}" class="mb-1 block rounded-xl px-3 py-2 text-sm {{ $language->code === $siteCurrentLocale ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground' }}">
                                    {{ $language->native_name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="hidden items-center gap-2 xl:flex">
                    @if($hasActiveMemberSession)
                        <div class="relative" x-data="{ memberMenuOpen: false }" @click.outside="memberMenuOpen = false">
                            <button
                                type="button"
                                class="kt-btn kt-btn-primary"
                                @click="memberMenuOpen = !memberMenuOpen"
                                :aria-expanded="memberMenuOpen"
                                aria-haspopup="true"
                            >
                                <i class="fa-solid fa-circle-user" aria-hidden="true"></i>
                                {{ $siteSettings->uiLine('nav_member_panel_label') }}
                                @if(($memberPendingReviewCount ?? 0) > 0)
                                    <span class="kt-badge kt-badge-sm kt-badge-light ms-1">{{ $memberPendingReviewCount }}</span>
                                @endif
                                <i class="fa-solid fa-chevron-down text-[10px]" aria-hidden="true"></i>
                            </button>

                            <div
                                x-show="memberMenuOpen"
                                x-cloak
                                x-transition.origin.top.right
                                class="site-member-header-menu absolute right-0 top-full z-50 mt-2 w-64 rounded-2xl border border-border bg-background p-2 shadow-xl"
                            >
                                <div class="border-b border-border px-3 py-2.5">
                                    <div class="truncate text-sm font-semibold text-foreground">{{ $siteMember->full_name }}</div>
                                    <div class="truncate text-xs text-muted-foreground">{{ $siteMember->email }}</div>
                                </div>
                                <a href="{{ route('member.account.show', ['site_locale' => $siteCurrentLocale]) }}" class="site-member-header-menu__item">Hesabım</a>
                                <a href="{{ route('member.appointments.index', ['site_locale' => $siteCurrentLocale]) }}" class="site-member-header-menu__item">Randevularım</a>
                                <a href="{{ route('member.projects.index', ['site_locale' => $siteCurrentLocale]) }}" class="site-member-header-menu__item">Projelerim</a>
                                <a href="{{ route('member.reviews.index', ['site_locale' => $siteCurrentLocale]) }}" class="site-member-header-menu__item">
                                    <span>Değerlendirmelerim</span>
                                    @if(($memberPendingReviewCount ?? 0) > 0)
                                        <span class="kt-badge kt-badge-sm kt-badge-primary">{{ $memberPendingReviewCount }}</span>
                                    @endif
                                </a>
                                <form method="POST" action="{{ route('member.logout') }}" class="mt-1 border-t border-border pt-1">
                                    @csrf
                                    <button type="submit" class="site-member-header-menu__item w-full text-danger">{{ $siteSettings->uiLine('nav_logout_label') }}</button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('member.register', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-light">
                            {{ $siteSettings->uiLine('nav_member_register_label') }}
                        </a>
                        <a href="{{ route('member.login', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-primary">
                            {{ $siteSettings->uiLine('nav_member_login_label') }}
                        </a>
                    @endif
                </div>

                <button type="button" class="relative inline-flex size-10 items-center justify-center rounded-xl text-foreground hover:bg-muted/60 xl:hidden" @click="mobileOpen = !mobileOpen" :aria-expanded="mobileOpen" aria-controls="site-mobile-nav" aria-label="Menü">
                    <span class="absolute block h-0.5 w-5 rounded-full bg-current transition-transform duration-200" :class="mobileOpen ? '-rotate-45' : '-translate-y-1.5'"></span>
                    <span class="absolute block h-0.5 w-5 rounded-full bg-current transition-all duration-200" :class="mobileOpen ? 'opacity-0' : 'translate-y-1.5'"></span>
                </button>
            </div>
        </div>

        <div id="site-mobile-nav" x-show="mobileOpen" x-cloak @click.outside="mobileOpen = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="site-mobile-nav border-t border-border bg-background px-3 py-4 sm:px-4 xl:hidden">
            <nav class="grid gap-1" aria-label="Mobil ana menü" data-site-mobile-navigation>
                @foreach($sitePrimaryNavigation as $navItem)
                    @include('site.partials.navigation.mobile-item', ['navItem' => $navItem])
                @endforeach
            </nav>

            <div class="my-4 border-t border-border"></div>

            <div class="grid gap-2">
                @if($hasActiveMemberSession)
                    <a href="{{ route('member.account.show', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-light w-full justify-center">
                        {{ $siteSettings->uiLine('nav_member_account_label') }}
                    </a>
                    <a href="{{ route('member.projects.index', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-light w-full justify-center">
                        Projelerim
                    </a>
                    <a href="{{ route('member.reviews.index', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-light w-full justify-center">
                        Değerlendirmelerim{{ ($memberPendingReviewCount ?? 0) > 0 ? ' (' . $memberPendingReviewCount . ')' : '' }}
                    </a>
                    <a href="{{ route('member.appointments.index', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-primary w-full justify-center">
                        {{ $siteSettings->uiLine('nav_member_panel_label') }}
                    </a>

                    <form method="POST" action="{{ route('member.logout') }}">
                        @csrf
                        <button type="submit" class="kt-btn kt-btn-light w-full justify-center">{{ $siteSettings->uiLine('nav_logout_label') }}</button>
                    </form>
                @else
                    <a href="{{ route('member.register', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-light w-full justify-center">
                        {{ $siteSettings->uiLine('nav_member_register_label') }}
                    </a>
                    <a href="{{ route('member.login', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-primary w-full justify-center">
                        {{ $siteSettings->uiLine('nav_member_login_label') }}
                    </a>
                @endif
            </div>
        </div>
    </header>

    <main id="site-main" class="pb-12">
        @yield('content')
    </main>

    <footer class="border-t border-border bg-muted/40">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 {{ $hasFooterNavigation ? 'lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_minmax(0,1fr)]' : 'lg:grid-cols-2' }} lg:px-6">
            <div class="grid gap-4">
                <div class="font-display text-2xl text-foreground">{{ $publicSiteName }}</div>
                <div class="max-w-sm text-sm leading-7 text-muted-foreground">
                    {{ $siteSettings->localized('footer_note') ?: $publicSiteTagline }}
                </div>
                <div class="grid gap-2 text-sm text-muted-foreground">
                    @if($siteSettings->contact_email)
                        <a href="mailto:{{ $siteSettings->contact_email }}" class="break-all hover:text-foreground">{{ $siteSettings->contact_email }}</a>
                    @endif
                    @if($siteSettings->contact_phone)
                        <a href="tel:{{ $siteSettings->contact_phone }}" class="hover:text-foreground">{{ $siteSettings->contact_phone }}</a>
                    @endif
                    @if($siteSettings->localized('address_line'))
                        <div>{{ $siteSettings->localized('address_line') }}</div>
                    @endif
                </div>
            </div>

            @if($hasFooterNavigation)
                <div class="grid gap-3" data-site-footer-navigation>
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">{{ $siteSettings->uiLine('footer_navigation_label') }}</div>
                    <div class="grid gap-2">
                        @foreach($siteFooterNavigation as $navItem)
                            @include('site.partials.navigation.footer-item', ['navItem' => $navItem])
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="grid gap-3">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">{{ $siteSettings->uiLine('footer_social_label') }}</div>
                <div class="grid gap-2">
                    @foreach(['instagram' => 'Instagram', 'facebook' => 'Facebook', 'linkedin' => 'LinkedIn', 'youtube' => 'YouTube', 'x' => 'X / Twitter'] as $key => $label)
                        @if($siteSettings->social($key))
                            <a href="{{ $siteSettings->social($key) }}" target="_blank" class="text-sm text-muted-foreground hover:text-foreground">
                                {{ $label }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        <div class="border-t border-border py-5">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 text-xs text-muted-foreground lg:px-6">
                <span>&copy; {{ date('Y') }} {{ $publicSiteName }}</span>
            </div>
        </div>
    </footer>
</div>
@stack('site_vendor_js')
@stack('site_js')
</body>
</html>
