@extends('site.layouts.main.app')

@section('content')
    <div class="site-page">
        <section class="site-page-hero" data-reveal>
            <span class="site-eyebrow">Fikirler ve rehberler</span>
            <h1 class="site-title">Merak edenler için notlar.</h1>
            <p class="site-lead">Deneyimlerimizi, süreçlerden öğrendiklerimizi ve işinizi ileri taşıyacak pratik bilgileri düzenli olarak paylaşıyoruz.</p>
        </section>

        <form
            action="{{ \App\Support\Site\SiteLocalization::localizedRoute('site.blog.index', locale: $siteCurrentLocale) }}"
            method="GET"
            class="site-filter-panel"
            data-blog-filter-form
        >
            <div class="site-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                <input
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Başlık, konu veya anahtar kelime ara..."
                    autocomplete="off"
                    aria-label="Blog yazılarında ara"
                    data-blog-search
                >
            </div>
            <input type="hidden" name="category" value="{{ $categorySlug }}" data-blog-category-input>

            <div class="site-filter-chips" aria-label="Blog kategorileri">
                <a
                    href="{{ \App\Support\Site\SiteLocalization::localizedRoute('site.blog.index', array_filter(['q' => $search]), $siteCurrentLocale) }}"
                    class="site-chip {{ $categorySlug === '' ? 'is-active' : '' }}"
                    data-blog-category=""
                >
                    Tümü
                </a>
                @foreach($categories as $category)
                    @php
                        $localizedCategorySlug = $category->localizedValue('slug');
                    @endphp
                    <a
                        href="{{ \App\Support\Site\SiteLocalization::localizedRoute('site.blog.index', array_filter(['q' => $search, 'category' => $localizedCategorySlug]), $siteCurrentLocale) }}"
                        class="site-chip {{ $categorySlug === $localizedCategorySlug ? 'is-active' : '' }}"
                        data-blog-category="{{ $localizedCategorySlug }}"
                    >
                        {{ $category->localizedValue('name') }}
                        <span class="opacity-70">{{ $category->published_posts_count }}</span>
                    </a>
                @endforeach
            </div>
        </form>

        @if($featuredPost)
            @php
                $featuredUrl = \App\Support\Site\SiteLocalization::localizedRoute('site.blog.show', [
                    'slug' => $featuredPost->localizedValue('slug'),
                ], $siteCurrentLocale);
            @endphp
            <section class="mt-10 site-featured" data-reveal>
                <a href="{{ $featuredUrl }}" class="site-featured-media">
                    @if($featuredPost->featuredMediaUrl())
                        <img src="{{ $featuredPost->featuredMediaUrl() }}" alt="{{ $featuredPost->localizedValue('title') }}">
                    @endif
                </a>
                <div class="site-featured-copy">
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-white/60">Öne çıkan yazı</span>
                    <h2 class="mt-5 font-display text-3xl font-semibold leading-tight text-white lg:text-4xl">{{ $featuredPost->localizedValue('title') }}</h2>
                    <p class="mt-5 text-sm leading-7 text-white/70">{{ $featuredPost->excerptPreview(190) }}</p>
                    <a href="{{ $featuredUrl }}" class="mt-7 inline-flex items-center gap-2 text-sm font-semibold text-white">Yazıyı keşfet <span>→</span></a>
                </div>
            </section>
        @endif

        <section class="mt-12" data-blog-results-section>
            <div class="site-section-heading mb-7">
                <div>
                    <span class="site-eyebrow">Kütüphane</span>
                    <h2 class="mt-4 site-section-title">Son yazılar</h2>
                </div>
                <span class="hidden text-sm text-muted-foreground md:block">Yeni bakış açıları, sade anlatımlar.</span>
            </div>
            @include('site.blog.partials.results', ['posts' => $posts])
        </section>
    </div>
@endsection

@push('site_js')
    @vite('resources/js/site/blog-index.js')
@endpush
