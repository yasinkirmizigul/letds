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
            @foreach($sitePrimaryNavigation as $navItem)
                @php($navItemCurrent = $navItem->isCurrent($locale))
                <a
                    href="{{ $navItem->resolvedUrl($locale) }}"
                    target="{{ $navItem->target }}"
                    @if($navItem->target === '_blank') rel="noopener noreferrer" @endif
                    @if($navItemCurrent) aria-current="page" @endif
                    class="home-mobile-menu__link {{ $navItemCurrent ? 'is-current' : '' }}"
                >
                    {{ $navItem->localized('title') }}
                </a>

                @foreach($navItem->children as $childItem)
                    @php($childCurrent = $childItem->isCurrent($locale))
                    <a
                        href="{{ $childItem->resolvedUrl($locale) }}"
                        target="{{ $childItem->target }}"
                        @if($childItem->target === '_blank') rel="noopener noreferrer" @endif
                        @if($childCurrent) aria-current="page" @endif
                        class="home-mobile-menu__link home-mobile-menu__link--child {{ $childCurrent ? 'is-current' : '' }}"
                    >
                        {{ $childItem->localized('title') }}
                    </a>
                @endforeach
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
                <a href="{{ route('member.register', ['site_locale' => $locale]) }}" class="home-mobile-menu__action">{{ $siteSettings->uiLine('nav_member_register_label') }}</a>
                <a href="{{ route('member.login', ['site_locale' => $locale]) }}" class="home-mobile-menu__action home-mobile-menu__action--primary">{{ $siteSettings->uiLine('nav_member_login_label') }}</a>
            @endif
        </div>
    </div>
</div>
