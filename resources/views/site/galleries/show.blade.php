@extends('site.layouts.main.app')

@php
    $imageItems = $gallery->items->filter(fn ($item) => $item->media?->isImage())->values();
    $cover = $imageItems->first()?->media;
@endphp

@if($cover)
    @push('site_meta')
        <meta property="og:image" content="{{ $cover->url('optimized') }}">
    @endpush
@endif

@section('content')
    <div class="site-page">
        <header class="site-page-hero" data-reveal>
            <a href="{{ \App\Support\Site\SiteLocalization::localizedRoute('site.galleries.index', locale: $siteCurrentLocale) }}" class="site-eyebrow">Galeriye dön</a>
            <h1 class="site-title">{{ $gallery->name }}</h1>
            @if($gallery->description)<p class="site-lead">{{ $gallery->description }}</p>@endif
            <div class="text-sm text-muted-foreground">{{ $imageItems->count() }} görsel</div>
        </header>

        <section class="mt-10 site-gallery-grid" aria-label="{{ $gallery->name }} görselleri">
            @foreach($imageItems as $index => $item)
                <button
                    type="button"
                    class="site-gallery-item"
                    data-gallery-item
                    data-gallery-index="{{ $index }}"
                    data-gallery-src="{{ $item->media->url('optimized') }}"
                    data-gallery-caption="{{ $item->caption ?: $item->media->localizedValue('title') ?: $gallery->name }}"
                    aria-label="{{ $item->caption ?: $gallery->name }} görselini büyüt"
                    data-reveal
                >
                    <img src="{{ $item->media->url('optimized') }}" alt="{{ $item->alt ?: $item->media->localizedValue('alt') ?: $item->caption ?: $gallery->name }}" loading="lazy">
                </button>
            @endforeach
        </section>

        <dialog class="site-lightbox" data-gallery-dialog aria-labelledby="gallery-lightbox-title" aria-describedby="gallery-lightbox-caption">
            <section class="site-lightbox__panel">
                <h2 id="gallery-lightbox-title" class="sr-only">{{ $gallery->name }} galeri görüntüleyicisi</h2>

                <button type="button" class="site-lightbox__control site-lightbox__close" data-gallery-close aria-label="Galeriyi kapat">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M6 6l12 12M18 6 6 18" />
                    </svg>
                </button>

                <div class="site-lightbox__viewport" data-gallery-viewport>
                    <div class="site-lightbox__loader" data-gallery-loader aria-hidden="true"><span></span></div>

                    <button type="button" class="site-lightbox__control site-lightbox__previous" data-gallery-prev aria-label="Önceki görsel">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m15 18-6-6 6-6" />
                        </svg>
                    </button>

                    <img src="" alt="" draggable="false" data-gallery-dialog-image>

                    <button type="button" class="site-lightbox__control site-lightbox__next" data-gallery-next aria-label="Sonraki görsel">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m9 18 6-6-6-6" />
                        </svg>
                    </button>
                </div>

                <footer class="site-lightbox__footer">
                    <span id="gallery-lightbox-caption" class="site-lightbox__caption" data-gallery-dialog-caption></span>
                    <span class="site-lightbox__counter" data-gallery-dialog-counter aria-live="polite"></span>
                </footer>
            </section>
        </dialog>
    </div>

    @if($relatedGalleries->isNotEmpty())
        <section class="site-page !pt-0">
            <div class="site-section-heading mb-7">
                <div>
                    <span class="site-eyebrow">Daha fazlası</span>
                    <h2 class="mt-4 site-section-title">Diğer galeriler</h2>
                </div>
            </div>
            <div class="site-card-grid">
                @foreach($relatedGalleries as $gallery)
                    @include('site.galleries.partials.card', ['gallery' => $gallery])
                @endforeach
            </div>
        </section>
    @endif
@endsection

@push('site_js')
    @vite('resources/js/site/gallery-lightbox.js')
@endpush
