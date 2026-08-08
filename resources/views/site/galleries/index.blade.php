@extends('site.layouts.main.app')

@section('content')
    <div class="site-page">
        <section class="site-page-hero" data-reveal>
            <span class="site-eyebrow">Seçili çalışmalar</span>
            <h1 class="site-title">Detaylarda yaşayan işler.</h1>
            <p class="site-lead">Fikirden son dokunuşa uzanan süreçlerden, tamamlanan projelerden ve üretim anlarından görsel notlar.</p>
        </section>

        <form action="{{ \App\Support\Site\SiteLocalization::localizedRoute('site.galleries.index', locale: $siteCurrentLocale) }}" method="GET" class="site-filter-panel sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
            <div class="site-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                <input type="search" name="q" value="{{ $search }}" placeholder="Galeri adı veya açıklama ara..." aria-label="Galerilerde ara">
            </div>
            <button type="submit" class="kt-btn kt-btn-primary min-h-12 rounded-full px-6">Ara</button>
        </form>

        <section class="mt-12">
            <div class="site-section-heading mb-7">
                <div>
                    <span class="site-eyebrow">Arşiv</span>
                    <h2 class="mt-4 site-section-title">{{ $search ? 'Arama sonuçları' : 'Tüm galeriler' }}</h2>
                </div>
                <span class="text-sm text-muted-foreground">{{ number_format($galleries->total()) }} galeri</span>
            </div>

            @if($galleries->isNotEmpty())
                <div class="site-card-grid">
                    @foreach($galleries as $gallery)
                        @include('site.galleries.partials.card', ['gallery' => $gallery])
                    @endforeach
                </div>
                <div class="mt-8">{{ $galleries->links() }}</div>
            @else
                <div class="site-empty">
                    <div>
                        <div class="site-eyebrow mx-auto">Henüz boş</div>
                        <h2 class="mt-5 site-section-title">Bu aramaya uygun galeri bulunamadı.</h2>
                        <a href="{{ \App\Support\Site\SiteLocalization::localizedRoute('site.galleries.index', locale: $siteCurrentLocale) }}" class="kt-btn kt-btn-primary mt-6">Tüm galerileri göster</a>
                    </div>
                </div>
            @endif
        </section>
    </div>
@endsection
