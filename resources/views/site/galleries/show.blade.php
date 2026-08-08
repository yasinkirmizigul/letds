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

        <dialog class="site-lightbox" data-gallery-dialog aria-label="Galeri görseli">
            <div class="relative">
                <button type="button" class="absolute right-3 top-3 z-10 grid size-11 place-items-center rounded-full bg-black/55 text-xl text-white backdrop-blur" data-gallery-close aria-label="Kapat">×</button>
                <button type="button" class="absolute left-3 top-1/2 z-10 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-black/55 text-xl text-white backdrop-blur" data-gallery-prev aria-label="Önceki görsel">←</button>
                <img src="" alt="" data-gallery-dialog-image>
                <button type="button" class="absolute right-3 top-1/2 z-10 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-black/55 text-xl text-white backdrop-blur" data-gallery-next aria-label="Sonraki görsel">→</button>
                <div class="flex items-center justify-between gap-4 px-5 py-4 text-sm text-white/75">
                    <span data-gallery-dialog-caption></span>
                    <span data-gallery-dialog-counter></span>
                </div>
            </div>
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
