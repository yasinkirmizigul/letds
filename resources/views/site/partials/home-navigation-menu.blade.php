@php
    $servicesUrl = \App\Support\Site\SiteLocalization::localizedRoute('site.services.index', locale: $locale);
    $aboutNavigationItem = $sitePrimaryNavigation->first(
        fn ($navItem) => $navItem->page?->slug === 'hakkimizda'
    );
    $aboutUrl = $aboutNavigationItem?->resolvedUrl($locale)
        ?? route('site.pages.show', ['slug' => 'hakkimizda']);

    $homeNavigationItems = collect([
        ['label' => 'Neler Sunuyoruz?', 'url' => $servicesUrl . '#hizmetler'],
        ['label' => 'Nasıl İlerliyoruz?', 'url' => $servicesUrl . '#nasil-ilerliyoruz'],
        ['label' => 'Birlikte Başlayalım', 'url' => $servicesUrl . '#birlikte-baslayalim'],
        ['label' => 'Hakkımızda', 'url' => $aboutUrl],
        ['label' => 'SSS', 'url' => \App\Support\Site\SiteLocalization::localizedRoute('site.faqs.index', locale: $locale)],
    ])->map(fn ($navItem) => [
        ...$navItem,
        'target' => null,
        'is_current' => false,
    ]);
@endphp

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
            <small>Sayfalar</small>
        </div>

        <nav class="home-mobile-menu__links" aria-label="Ana menü">
            @foreach($homeNavigationItems as $navItem)
                <a
                    href="{{ $navItem['url'] }}"
                    class="home-mobile-menu__link {{ $navItem['is_current'] ? 'is-current' : '' }}"
                    @if($navItem['is_current']) aria-current="page" @endif
                    @if($navItem['target'] === '_blank') target="_blank" rel="noopener noreferrer" @endif
                >
                    {{ $navItem['label'] }}
                </a>
            @endforeach
        </nav>

    </div>
</div>
