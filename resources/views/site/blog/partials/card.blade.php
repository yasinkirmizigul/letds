@php
    $postUrl = \App\Support\Site\SiteLocalization::localizedRoute('site.blog.show', [
        'slug' => $post->localizedValue('slug'),
    ], $siteCurrentLocale);
    $imageUrl = $post->featuredMediaUrl();
@endphp

<article class="site-card" data-reveal>
    <a href="{{ $postUrl }}" class="site-card-media" tabindex="-1" aria-hidden="true">
        @if($imageUrl)
            <img src="{{ $imageUrl }}" alt="{{ $post->localizedValue('title') }}" loading="lazy">
        @else
            <span class="site-card-placeholder"><span>{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($post->localizedValue('title'), 0, 1)) }}</span></span>
        @endif
    </a>

    <div class="site-card-body">
        <div class="site-card-meta">
            <span>{{ optional($post->published_at)->format('d.m.Y') ?: 'Yeni' }}</span>
            @if($post->estimatedReadTimeMinutes() > 0)
                <span>{{ $post->estimatedReadTimeMinutes() }} dk okuma</span>
            @endif
        </div>

        @if($post->categories->isNotEmpty())
            <div class="flex flex-wrap gap-2">
                @foreach($post->categories->take(2) as $category)
                    <span class="text-xs font-semibold text-primary">{{ $category->localizedValue('name') }}</span>
                @endforeach
            </div>
        @endif

        <h2 class="site-card-title">
            <a href="{{ $postUrl }}" class="after:absolute after:inset-0">{{ $post->localizedValue('title') }}</a>
        </h2>
        <p class="text-sm leading-7 text-muted-foreground">{{ $post->excerptPreview(145) }}</p>

        <span class="mt-auto inline-flex items-center gap-2 text-sm font-semibold text-primary" aria-hidden="true">
            Yazıyı oku <span>→</span>
        </span>
    </div>
</article>
