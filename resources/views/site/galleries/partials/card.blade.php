@php
    $galleryUrl = \App\Support\Site\SiteLocalization::localizedRoute('site.galleries.show', [
        'slug' => $gallery->slug,
    ], $siteCurrentLocale);
    $cover = $gallery->coverItem?->media;
@endphp

<article class="site-card" data-reveal>
    <a href="{{ $galleryUrl }}" class="site-card-media" tabindex="-1" aria-hidden="true">
        @if($cover?->isImage())
            <img src="{{ $cover->url('optimized') }}" alt="{{ $gallery->name }}" loading="lazy">
        @else
            <span class="site-card-placeholder"><span>{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($gallery->name, 0, 1)) }}</span></span>
        @endif
    </a>
    <div class="site-card-body">
        <div class="site-card-meta">
            <span>{{ number_format($gallery->items_count) }} çalışma</span>
            @if($gallery->published_at)<span>{{ $gallery->published_at->format('d.m.Y') }}</span>@endif
        </div>
        <h2 class="site-card-title">
            <a href="{{ $galleryUrl }}" class="after:absolute after:inset-0">{{ $gallery->name }}</a>
        </h2>
        @if($gallery->description)
            <p class="text-sm leading-7 text-muted-foreground">{{ \Illuminate\Support\Str::limit(strip_tags($gallery->description), 145) }}</p>
        @endif
        <span class="mt-auto inline-flex items-center gap-2 text-sm font-semibold text-primary" aria-hidden="true">Seçkiyi gör <span>→</span></span>
    </div>
</article>
