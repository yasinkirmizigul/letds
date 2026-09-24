@php
    $homeNavigationItems = collect([
        ['label' => 'Neler Sunuyoruz?', 'url' => '#neler-sunuyoruz'],
        ['label' => 'Nasıl İlerliyoruz?', 'url' => '#nasil-ilerliyoruz'],
        ['label' => 'Birlikte Başlayalım', 'url' => '#birlikte-baslayalim'],
        ['label' => 'Hakkımızda', 'url' => '#hakkimizda'],
        ['label' => 'SSS', 'url' => '#sss'],
    ]);
@endphp

<div class="home-desktop-navigation">
    <nav class="home-desktop-navigation__links" aria-label="Ana menü">
        @foreach($homeNavigationItems as $navItem)
            <a
                href="{{ $navItem['url'] }}"
                class="home-desktop-navigation__link"
            >
                {{ $navItem['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="home-desktop-navigation__actions">
        @if($hasActiveMemberSession)
            <a href="{{ route('member.account.show', ['site_locale' => $locale]) }}" class="home-desktop-navigation__action">Hesabım</a>
            <a href="{{ route('member.appointments.index', ['site_locale' => $locale]) }}" class="home-desktop-navigation__action home-desktop-navigation__action--primary">{{ $siteSettings->uiLine('nav_member_panel_label') }}</a>
        @else
            <a href="{{ route('member.register', ['site_locale' => $locale]) }}" class="home-desktop-navigation__action home-desktop-navigation__action--primary">Kayıt Ol</a>
            <a href="{{ route('member.login', ['site_locale' => $locale]) }}" class="home-desktop-navigation__action">Giriş Yap</a>
        @endif
    </div>
</div>

<div class="home-mobile-menu" data-home-navigation data-open="false">
    <button
        type="button"
        class="home-mobile-menu__trigger"
        aria-label="Site menüsünü aç"
        aria-controls="home-navigation-panel"
        aria-expanded="false"
        data-home-navigation-toggle
    >
        <span aria-hidden="true"></span>
        <span aria-hidden="true"></span>
        <span aria-hidden="true"></span>
    </button>

    <div id="home-navigation-panel" class="home-mobile-menu__panel" aria-hidden="true" data-home-navigation-panel inert>
        <div class="home-mobile-menu__heading">
            <span>Menü</span>
            <small>Sayfalar ve üye işlemleri</small>
        </div>

        <nav class="home-mobile-menu__links" aria-label="Ana menü">
            @foreach($homeNavigationItems as $navItem)
                <a
                    href="{{ $navItem['url'] }}"
                    class="home-mobile-menu__link"
                >
                    {{ $navItem['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="home-mobile-menu__actions">
            @if($hasActiveMemberSession)
                <a href="{{ route('member.account.show', ['site_locale' => $locale]) }}" class="home-mobile-menu__action">Hesabım</a>
                <a href="{{ route('member.appointments.index', ['site_locale' => $locale]) }}" class="home-mobile-menu__action home-mobile-menu__action--primary">{{ $siteSettings->uiLine('nav_member_panel_label') }}</a>
                <form method="POST" action="{{ route('member.logout') }}">
                    @csrf
                    <button type="submit" class="home-mobile-menu__action">{{ $siteSettings->uiLine('nav_logout_label') }}</button>
                </form>
            @else
                <a href="{{ route('member.register', ['site_locale' => $locale]) }}" class="home-mobile-menu__action home-mobile-menu__action--primary">Kayıt Ol</a>
                <a href="{{ route('member.login', ['site_locale' => $locale]) }}" class="home-mobile-menu__action">Giriş Yap</a>
            @endif
        </div>
    </div>
</div>
