<div id="blog-results" data-blog-results aria-live="polite" aria-busy="false">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3 text-sm text-muted-foreground">
        <span><strong class="text-foreground">{{ number_format($posts->total()) }}</strong> yazı bulundu</span>
        @if(request('q') || request('category'))
            <a href="{{ \App\Support\Site\SiteLocalization::localizedRoute('site.blog.index', locale: $siteCurrentLocale) }}" class="font-semibold text-primary">Filtreleri temizle</a>
        @endif
    </div>

    @if($posts->isNotEmpty())
        <div class="site-card-grid">
            @foreach($posts as $post)
                @include('site.blog.partials.card', ['post' => $post])
            @endforeach
        </div>
        <div class="mt-8">{{ $posts->links() }}</div>
    @else
        <div class="site-empty">
            <div>
                <div class="site-eyebrow mx-auto">Sonuç bulunamadı</div>
                <h2 class="mt-5 site-section-title">Aradığınız içerik henüz burada değil.</h2>
                <p class="mx-auto mt-3 max-w-lg text-sm leading-7 text-muted-foreground">Arama ifadenizi kısaltabilir veya başka bir kategori seçebilirsiniz.</p>
                <a href="{{ \App\Support\Site\SiteLocalization::localizedRoute('site.blog.index', locale: $siteCurrentLocale) }}" class="kt-btn kt-btn-primary mt-6">Tüm yazıları göster</a>
            </div>
        </div>
    @endif
</div>
