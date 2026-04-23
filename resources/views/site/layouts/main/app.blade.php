<!DOCTYPE html>
<html lang="{{ $siteCurrentLocale }}" dir="{{ $siteCurrentLanguage?->is_rtl ? 'rtl' : 'ltr' }}" data-kt-theme="true" data-kt-theme-mode="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ ($pageTitle ?? null) ? $pageTitle . ' | ' . ($siteSettings->localized('site_name') ?: config('app.name')) : ($siteSettings->localized('site_name') ?: config('app.name')) }}</title>

    <script defer src="{{ asset('assets/js/core.bundle.js') }}"></script>
    <script defer src="{{ asset('assets/vendors/ktui/ktui.min.js') }}"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-background text-foreground">
<div class="min-h-screen bg-[radial-gradient(circle_at_top_left,rgba(62,151,255,0.12),transparent_24%),linear-gradient(180deg,#f8fafc_0%,#ffffff_100%)]">
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

    <header class="sticky top-0 z-40 border-b border-border/80 bg-white/85 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center justify-between gap-4">
                <a href="{{ \App\Support\Site\SiteLocalization::homeUrl($siteCurrentLocale) }}" class="flex items-center gap-3">
                    <span class="inline-flex size-12 items-center justify-center rounded-2xl bg-primary text-sm font-semibold text-white">
                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($siteSettings->localized('site_name') ?: config('app.name'), 0, 2)) }}
                    </span>
                    <span class="grid">
                        <span class="text-base font-semibold text-foreground">{{ $siteSettings->localized('site_name') ?: config('app.name') }}</span>
                        <span class="text-sm text-muted-foreground">{{ $siteSettings->localized('site_tagline') ?: 'Dijital vitrin ve içerik yönetimi' }}</span>
                    </span>
                </a>
            </div>

            <nav class="flex flex-wrap items-center gap-2">
                <a href="{{ \App\Support\Site\SiteLocalization::homeUrl($siteCurrentLocale) }}" class="rounded-full px-4 py-2 text-sm font-medium {{ request()->routeIs('site.home', 'site.home.localized') ? 'bg-primary text-white' : 'text-foreground hover:bg-muted/60' }}">
                    {{ $siteSettings->uiLine('nav_home_label') }}
                </a>

                @foreach($sitePrimaryNavigation as $navItem)
                    @if($navItem->children->isNotEmpty())
                        <details class="group relative">
                            <summary class="list-none cursor-pointer rounded-full px-4 py-2 text-sm font-medium text-foreground hover:bg-muted/60">
                                {{ $navItem->localized('title') }}
                            </summary>
                            <div class="absolute left-0 top-full mt-2 min-w-[220px] rounded-3xl border border-border bg-white p-3 shadow-xl">
                                <a href="{{ $navItem->resolvedUrl($siteCurrentLocale) }}" target="{{ $navItem->target }}" class="block rounded-2xl px-4 py-3 text-sm font-medium text-foreground hover:bg-muted/50">
                                    {{ $navItem->localized('title') }}
                                </a>
                                @foreach($navItem->children as $childItem)
                                    <a href="{{ $childItem->resolvedUrl($siteCurrentLocale) }}" target="{{ $childItem->target }}" class="block rounded-2xl px-4 py-3 text-sm text-muted-foreground hover:bg-muted/50 hover:text-foreground">
                                        {{ $childItem->localized('title') }}
                                    </a>
                                @endforeach
                            </div>
                        </details>
                    @else
                        <a href="{{ $navItem->resolvedUrl($siteCurrentLocale) }}" target="{{ $navItem->target }}" class="rounded-full px-4 py-2 text-sm font-medium text-foreground hover:bg-muted/60">
                            {{ $navItem->localized('title') }}
                        </a>
                    @endif
                @endforeach
            </nav>

            <div class="flex flex-wrap items-center gap-2">
                @if($siteLanguages->count() > 1)
                    <details class="relative">
                        <summary class="kt-btn kt-btn-light list-none cursor-pointer">
                            {{ $siteCurrentLanguage?->native_name ?: strtoupper($siteCurrentLocale) }}
                        </summary>
                        <div class="absolute right-0 top-full mt-2 min-w-[180px] rounded-3xl border border-border bg-white p-3 shadow-xl">
                            @foreach($siteLanguages as $language)
                                <a href="{{ \App\Support\Site\SiteLocalization::switchUrl(request(), $language->code, $currentSitePage ?? null) }}" class="mb-1 block rounded-2xl px-4 py-3 text-sm {{ $language->code === $siteCurrentLocale ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground' }}">
                                    {{ $language->native_name }}
                                </a>
                            @endforeach
                        </div>
                    </details>
                @endif

                <a href="{{ route('site.contact-messages.create', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-light">
                    {{ $siteSettings->uiLine('nav_contact_label') }}
                </a>

                @if(auth('member')->check())
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
        </div>
    </header>

    <main class="pb-12">
        @yield('content')
    </main>

    <footer class="border-t border-border bg-white/80">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)_minmax(0,0.8fr)]">
            <div class="grid gap-3">
                <div class="text-lg font-semibold text-foreground">{{ $siteSettings->localized('site_name') ?: config('app.name') }}</div>
                <div class="text-sm leading-7 text-muted-foreground">
                    {{ $siteSettings->localized('footer_note') ?: ($siteSettings->localized('site_tagline') ?: 'Dijital vitrin ve içerik yönetimi') }}
                </div>
                <div class="grid gap-2 text-sm text-muted-foreground">
                    @if($siteSettings->contact_email)
                        <div>{{ $siteSettings->contact_email }}</div>
                    @endif
                    @if($siteSettings->contact_phone)
                        <div>{{ $siteSettings->contact_phone }}</div>
                    @endif
                    @if($siteSettings->localized('address_line'))
                        <div>{{ $siteSettings->localized('address_line') }}</div>
                    @endif
                </div>
            </div>

            <div class="grid gap-3">
                <div class="text-sm font-semibold uppercase tracking-[0.24em] text-muted-foreground">{{ $siteSettings->uiLine('footer_navigation_label') }}</div>
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
                <div class="text-sm font-semibold uppercase tracking-[0.24em] text-muted-foreground">{{ $siteSettings->uiLine('footer_social_label') }}</div>
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
    </footer>
</div>
</body>
</html>
