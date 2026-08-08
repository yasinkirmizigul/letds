<nav class="site-member-nav" aria-label="Üye alanı">
    <a href="{{ route('member.account.show', ['site_locale' => $siteCurrentLocale]) }}" class="{{ request()->routeIs('member.account.show') ? 'is-active' : '' }}">Özet</a>
    <a href="{{ route('member.account.edit', ['site_locale' => $siteCurrentLocale]) }}" class="{{ request()->routeIs('member.account.edit') ? 'is-active' : '' }}">Profil</a>
    <a href="{{ route('member.appointments.index', ['site_locale' => $siteCurrentLocale]) }}" class="{{ request()->routeIs('member.appointments.*') ? 'is-active' : '' }}">Randevu</a>
    <a href="{{ route('member.projects.index', ['site_locale' => $siteCurrentLocale]) }}" class="{{ request()->routeIs('member.projects.*') ? 'is-active' : '' }}">Projeler</a>
    <a href="{{ route('member.reviews.index', ['site_locale' => $siteCurrentLocale]) }}" class="{{ request()->routeIs('member.reviews.*') ? 'is-active' : '' }}">
        Değerlendirmeler
        @if(($memberPendingReviewCount ?? 0) > 0)<span class="ms-1">{{ $memberPendingReviewCount }}</span>@endif
    </a>
</nav>
