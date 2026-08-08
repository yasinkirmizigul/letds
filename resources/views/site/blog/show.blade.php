@extends('site.layouts.main.app')

@if($post->featuredMediaUrl())
    @push('site_meta')
        <meta property="og:image" content="{{ $post->featuredMediaUrl() }}">
    @endpush
@endif

@section('content')
    <article class="site-page site-page-narrow">
        <header class="text-center" data-reveal>
            <a href="{{ \App\Support\Site\SiteLocalization::localizedRoute('site.blog.index', locale: $siteCurrentLocale) }}" class="site-eyebrow mx-auto">Bloga dön</a>
            <h1 class="mx-auto mt-6 site-title">{{ $post->localizedValue('title') }}</h1>
            @if($post->localizedValue('excerpt'))
                <p class="mx-auto mt-6 site-lead">{{ $post->localizedValue('excerpt') }}</p>
            @endif
            <div class="mt-6 flex flex-wrap justify-center gap-x-5 gap-y-2 text-sm text-muted-foreground">
                <span>{{ optional($post->published_at)->format('d.m.Y') }}</span>
                @if($post->author)<span>{{ $post->author->name }}</span>@endif
                @if($post->estimatedReadTimeMinutes() > 0)<span>{{ $post->estimatedReadTimeMinutes() }} dk okuma</span>@endif
            </div>
            @if($post->categories->isNotEmpty())
                <div class="mt-5 flex flex-wrap justify-center gap-2">
                    @foreach($post->categories as $category)
                        <a href="{{ \App\Support\Site\SiteLocalization::localizedRoute('site.blog.index', ['category' => $category->localizedValue('slug')], $siteCurrentLocale) }}" class="site-chip">{{ $category->localizedValue('name') }}</a>
                    @endforeach
                </div>
            @endif
        </header>

        @if($post->featuredMediaUrl())
            <figure class="mt-10 overflow-hidden rounded-[2rem] border border-border bg-muted shadow-xl" data-reveal>
                <img src="{{ $post->featuredMediaUrl() }}" alt="{{ $post->localizedValue('title') }}" class="aspect-[16/9] w-full object-cover">
            </figure>
        @endif

        <div class="site-prose mx-auto mt-12 max-w-3xl" data-reveal>
            {!! $post->localizedValue('content') !!}
        </div>

        @foreach($post->galleries as $gallery)
            @if($gallery->items->isNotEmpty())
                <section class="mt-14" data-reveal>
                    <div class="site-section-heading mb-6">
                        <h2 class="site-section-title">{{ $gallery->name }}</h2>
                        <a href="{{ \App\Support\Site\SiteLocalization::localizedRoute('site.galleries.show', ['slug' => $gallery->slug], $siteCurrentLocale) }}" class="text-sm font-semibold text-primary">Galeriyi aç</a>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-3">
                        @foreach($gallery->items->take(3) as $item)
                            @if($item->media?->isImage())
                                <img src="{{ $item->media->url('optimized') }}" alt="{{ $item->alt ?: $item->caption ?: $gallery->name }}" class="aspect-square w-full rounded-2xl object-cover" loading="lazy">
                            @endif
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach
    </article>

    @if($relatedPosts->isNotEmpty())
        <section class="site-page !pt-0">
            <div class="site-section-heading mb-7">
                <div>
                    <span class="site-eyebrow">Okumaya devam</span>
                    <h2 class="mt-4 site-section-title">İlgili yazılar</h2>
                </div>
            </div>
            <div class="site-card-grid">
                @foreach($relatedPosts as $post)
                    @include('site.blog.partials.card', ['post' => $post])
                @endforeach
            </div>
        </section>
    @endif
@endsection
