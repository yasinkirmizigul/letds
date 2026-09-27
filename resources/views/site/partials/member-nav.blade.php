@php($memberUnreadWorkflowCount = auth('member')->user()?->projects()->whereHas('workflowEvents', fn ($query) => $query->whereNull('member_seen_at'))->count() ?? 0)
<nav class="site-member-nav" aria-label="Üye alanı">
    <a href="{{ route('member.account.show', ['site_locale' => $siteCurrentLocale]) }}" class="{{ request()->routeIs('member.account.show') ? 'is-active' : '' }}">Ana Sayfam</a>
    <a href="{{ route('member.appointments.index', ['site_locale' => $siteCurrentLocale]) }}" class="{{ request()->routeIs('member.appointments.*') ? 'is-active' : '' }}">Randevularım</a>
    <a href="{{ route('member.projects.index', ['site_locale' => $siteCurrentLocale]) }}" class="{{ request()->routeIs('member.projects.*') ? 'is-active' : '' }}">Analiz Sürecim @if($memberUnreadWorkflowCount > 0)<span class="ms-1 rounded-full bg-primary px-2 py-0.5 text-xs text-primary-foreground" aria-label="{{ $memberUnreadWorkflowCount }} yeni süreç bildirimi">{{ $memberUnreadWorkflowCount }}</span>@endif</a>
    <a href="{{ route('member.projects.index', ['site_locale' => $siteCurrentLocale]) }}#belgelerim">Belgelerim</a>
    <a href="{{ route('member.account.show', ['site_locale' => $siteCurrentLocale]) }}#bilmeniz-gerekenler">Bilmeniz Gerekenler</a>
    <a href="{{ route('member.projects.index', ['site_locale' => $siteCurrentLocale]) }}#sonuclarim">Sonuçlarım</a>
    <a href="{{ route('member.account.edit', ['site_locale' => $siteCurrentLocale]) }}" class="{{ request()->routeIs('member.account.edit') ? 'is-active' : '' }}">Profilim</a>
    <a href="{{ route('member.reviews.index', ['site_locale' => $siteCurrentLocale]) }}" class="{{ request()->routeIs('member.reviews.*') ? 'is-active' : '' }}">
        Değerlendirmelerim
        @if(($memberPendingReviewCount ?? 0) > 0)<span class="ms-1">{{ $memberPendingReviewCount }}</span>@endif
    </a>
</nav>
