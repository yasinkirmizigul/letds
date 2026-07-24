<!DOCTYPE html>
<html lang="{{ $siteCurrentLocale }}" dir="{{ $siteCurrentLanguage?->is_rtl ? 'rtl' : 'ltr' }}" data-kt-theme="true" data-kt-theme-mode="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- JS erken işareti: reveal animasyonlarının gizli başlangıç durumu yalnızca JS çalışırken uygulanır (script yüklenmezse içerik görünür kalır) --}}
    <script>document.documentElement.classList.add('site-js');</script>
    <title>{{ ($pageTitle ?? null) ? $pageTitle . ' | ' . ($siteSettings->localized('site_name') ?: config('app.name')) : ($siteSettings->localized('site_name') ?: config('app.name')) }}</title>

    @stack('site_vendor_css')
    <script defer src="{{ asset('assets/js/core.bundle.js') }}"></script>
    <script defer src="{{ asset('assets/vendors/ktui/ktui.min.js') }}"></script>
    @vite(['resources/css/app.css', 'resources/js/site/app.js'])
    @stack('site_css')
</head>
<body class="site-shell min-h-screen bg-background text-foreground">
@php
    $siteMember = auth('member')->user();
    $hasActiveMemberSession = $siteMember && $siteMember->is_active && !$siteMember->trashed();
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

    <header class="sticky top-0 z-40 border-b border-border bg-background/90 backdrop-blur-xl" x-data="{ mobileOpen: false }">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-6 px-4 lg:h-[72px] lg:px-6">
            <a href="{{ \App\Support\Site\SiteLocalization::homeUrl($siteCurrentLocale) }}" class="flex items-center gap-3">
                <span class="inline-flex size-10 items-center justify-center rounded-2xl bg-primary text-sm font-semibold text-white lg:size-12">
                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($siteSettings->localized('site_name') ?: config('app.name'), 0, 2)) }}
                </span>
                <span class="grid">
                    <span class="text-base font-semibold text-foreground">{{ $siteSettings->localized('site_name') ?: config('app.name') }}</span>
                    <span class="hidden text-sm text-muted-foreground lg:block">{{ $siteSettings->localized('site_tagline') ?: 'Dijital vitrin ve içerik yönetimi' }}</span>
                </span>
            </a>

            <nav class="hidden items-center gap-1 lg:flex">
                <a href="{{ \App\Support\Site\SiteLocalization::homeUrl($siteCurrentLocale) }}" class="relative px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('site.home', 'site.home.localized') ? "text-primary after:absolute after:inset-x-3 after:-bottom-px after:h-0.5 after:rounded-full after:bg-primary after:content-['']" : 'text-muted-foreground hover:text-foreground' }}">
                    {{ $siteSettings->uiLine('nav_home_label') }}
                </a>

                @foreach($sitePrimaryNavigation as $navItem)
                    @if($navItem->children->isNotEmpty())
                        <div x-data="{ open: false }" @click.outside="open = false" class="relative">
                            <button type="button" @click="open = !open" :aria-expanded="open" aria-haspopup="true" class="relative px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground">
                                {{ $navItem->localized('title') }}
                            </button>
                            <div x-show="open" x-cloak x-transition.origin.top class="absolute left-0 top-full z-50 mt-2 min-w-[220px] rounded-2xl border border-border bg-background p-2 shadow-lg">
                                <a href="{{ $navItem->resolvedUrl($siteCurrentLocale) }}" target="{{ $navItem->target }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-foreground hover:bg-muted/50">
                                    {{ $navItem->localized('title') }}
                                </a>
                                @foreach($navItem->children as $childItem)
                                    <a href="{{ $childItem->resolvedUrl($siteCurrentLocale) }}" target="{{ $childItem->target }}" class="block rounded-xl px-3 py-2 text-sm text-muted-foreground hover:bg-muted/50 hover:text-foreground">
                                        {{ $childItem->localized('title') }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <a href="{{ $navItem->resolvedUrl($siteCurrentLocale) }}" target="{{ $navItem->target }}" class="relative px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground">
                            {{ $navItem->localized('title') }}
                        </a>
                    @endif
                @endforeach
            </nav>

            <div class="flex items-center gap-2">
                @if($siteLanguages->count() > 1)
                    <div x-data="{ open: false }" @click.outside="open = false" class="relative">
                        <button type="button" @click="open = !open" :aria-expanded="open" aria-haspopup="true" class="kt-btn kt-btn-light">
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

                <div class="hidden items-center gap-2 lg:flex">
                    <a href="{{ route('site.contact-messages.create', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-light">
                        {{ $siteSettings->uiLine('nav_contact_label') }}
                    </a>

                    @if($hasActiveMemberSession)
                        <a href="{{ route('member.account.show', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-light">
                            {{ $siteSettings->uiLine('nav_member_account_label') }}
                        </a>
                        <a href="{{ route('member.appointments.index', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-primary">
                            {{ $siteSettings->uiLine('nav_member_panel_label') }}
                        </a>

                        <form method="POST" action="{{ route('member.logout') }}">
                            @csrf
                            <button type="submit" class="kt-btn kt-btn-light">{{ $siteSettings->uiLine('nav_logout_label') }}</button>
                        </form>
                    @else
                        <a href="{{ route('member.register', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-light">
                            {{ $siteSettings->uiLine('nav_member_register_label') }}
                        </a>
                        <a href="{{ route('member.login', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-primary">
                            {{ $siteSettings->uiLine('nav_member_login_label') }}
                        </a>
                    @endif
                </div>

                <button type="button" class="relative inline-flex size-10 items-center justify-center rounded-xl text-foreground hover:bg-muted/60 lg:hidden" @click="mobileOpen = !mobileOpen" :aria-expanded="mobileOpen" aria-controls="site-mobile-nav" aria-label="Menü">
                    <span class="absolute block h-0.5 w-5 rounded-full bg-current transition-transform duration-200" :class="mobileOpen ? '-rotate-45' : '-translate-y-1.5'"></span>
                    <span class="absolute block h-0.5 w-5 rounded-full bg-current transition-all duration-200" :class="mobileOpen ? 'opacity-0' : 'translate-y-1.5'"></span>
                </button>
            </div>
        </div>

        <div id="site-mobile-nav" x-show="mobileOpen" x-cloak @click.outside="mobileOpen = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="border-t border-border bg-background px-4 py-4 lg:hidden">
            <nav class="grid gap-1">
                <a href="{{ \App\Support\Site\SiteLocalization::homeUrl($siteCurrentLocale) }}" class="rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('site.home', 'site.home.localized') ? 'text-primary' : 'text-foreground hover:bg-muted/60' }}">
                    {{ $siteSettings->uiLine('nav_home_label') }}
                </a>

                @foreach($sitePrimaryNavigation as $navItem)
                    <a href="{{ $navItem->resolvedUrl($siteCurrentLocale) }}" target="{{ $navItem->target }}" class="rounded-xl px-3 py-2 text-sm font-medium text-foreground hover:bg-muted/60">
                        {{ $navItem->localized('title') }}
                    </a>
                    @foreach($navItem->children as $childItem)
                        <a href="{{ $childItem->resolvedUrl($siteCurrentLocale) }}" target="{{ $childItem->target }}" class="rounded-xl px-3 py-2 pl-6 text-sm text-muted-foreground hover:bg-muted/60 hover:text-foreground">
                            {{ $childItem->localized('title') }}
                        </a>
                    @endforeach
                @endforeach
            </nav>

            <div class="my-4 border-t border-border"></div>

            <div class="grid gap-2">
                <a href="{{ route('site.contact-messages.create', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-light w-full justify-center">
                    {{ $siteSettings->uiLine('nav_contact_label') }}
                </a>

                @if($hasActiveMemberSession)
                    <a href="{{ route('member.account.show', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-light w-full justify-center">
                        {{ $siteSettings->uiLine('nav_member_account_label') }}
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
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_minmax(0,1fr)] lg:px-6">
            <div class="grid gap-4">
                <div class="font-display text-2xl text-foreground">{{ $siteSettings->localized('site_name') ?: config('app.name') }}</div>
                <div class="max-w-sm text-sm leading-7 text-muted-foreground">
                    {{ $siteSettings->localized('footer_note') ?: ($siteSettings->localized('site_tagline') ?: 'Dijital vitrin ve içerik yönetimi') }}
                </div>
                <div class="grid gap-2 text-sm text-muted-foreground">
                    @if($siteSettings->contact_email)
                        <a href="mailto:{{ $siteSettings->contact_email }}" class="hover:text-foreground">{{ $siteSettings->contact_email }}</a>
                    @endif
                    @if($siteSettings->contact_phone)
                        <a href="tel:{{ $siteSettings->contact_phone }}" class="hover:text-foreground">{{ $siteSettings->contact_phone }}</a>
                    @endif
                    @if($siteSettings->localized('address_line'))
                        <div>{{ $siteSettings->localized('address_line') }}</div>
                    @endif
                </div>
            </div>

            <div class="grid gap-3">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">{{ $siteSettings->uiLine('footer_navigation_label') }}</div>
                <div class="grid gap-2">
                    @forelse($siteFooterNavigation as $navItem)
                        <a href="{{ $navItem->resolvedUrl($siteCurrentLocale) }}" target="{{ $navItem->target }}" class="text-sm text-muted-foreground hover:text-foreground">
                            {{ $navItem->localized('title') }}
                        </a>
                        @foreach($navItem->children as $childItem)
                            <a href="{{ $childItem->resolvedUrl($siteCurrentLocale) }}" target="{{ $childItem->target }}" class="pl-4 text-sm text-muted-foreground hover:text-foreground">
                                {{ $childItem->localized('title') }}
                            </a>
                        @endforeach
                    @empty
                        <div class="text-sm text-muted-foreground">Alt menü öğesi henüz tanımlanmadı.</div>
                    @endforelse
                </div>
            </div>

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
                <span>&copy; {{ date('Y') }} {{ $siteSettings->localized('site_name') ?: config('app.name') }}</span>
            </div>
        </div>
    </footer>
</div>
@stack('site_vendor_js')
@stack('site_js')
</body>
</html>
