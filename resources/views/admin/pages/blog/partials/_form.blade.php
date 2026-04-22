@php
    $isEdit = isset($blogPost) && $blogPost;
    $currentTitle = old('title', $blogPost->title ?? '');
    $currentSlug = old('slug', $blogPost->slug ?? '');
    $currentExcerpt = old('excerpt', $blogPost->excerpt ?? '');
    $currentContent = old('content', $blogPost->content ?? '');
    $currentMetaTitle = old('meta_title', $blogPost->meta_title ?? '');
    $currentMetaDescription = old('meta_description', $blogPost->meta_description ?? '');
    $currentMetaKeywords = old('meta_keywords', $blogPost->meta_keywords ?? '');
    $currentPublished = (bool) old('is_published', (bool) ($blogPost->is_published ?? false));
    $currentFeatured = (bool) old('is_featured', (bool) ($blogPost->is_featured ?? false));
    $selectedCategoryIds = old('category_ids', $selectedCategoryIds ?? []);
    $featuredMediaId = old('featured_media_id', $featuredMediaId ?? null);
    $currentFeaturedUrl = $currentFeaturedUrl ?? null;
    $initialWordCount = $isEdit ? $blogPost->contentWordCount() : 0;
    $initialReadTime = $isEdit ? $blogPost->estimatedReadTimeMinutes() : 0;
    $initialSeoScore = $isEdit ? $blogPost->seoCompletenessScore() : 0;
    $previewTitle = $currentMetaTitle ?: ($currentTitle ?: 'Meta baslik burada gorunecek');
    $previewDescription = $currentMetaDescription ?: ($currentExcerpt ?: 'Meta aciklama veya ozet burada gorunecek.');
@endphp

<div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.6fr)_400px] gap-6">
    <div class="grid gap-6">
        <div class="kt-card overflow-hidden">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Yazi Govdesi</h3>
                    <div class="text-sm text-muted-foreground">
                        Baslik, ozet, slug ve ana icerigi tek akista duzenle.
                    </div>
                </div>
            </div>

            <div class="kt-card-content p-6 grid gap-6">
                <div class="grid gap-2">
                    <div class="flex items-center justify-between gap-3">
                        <label class="kt-form-label font-normal text-mono" for="title">Baslik</label>
                        <span class="text-xs text-muted-foreground" data-blog-title-count>{{ mb_strlen($currentTitle) }}/255</span>
                    </div>
                    <input
                        id="title"
                        name="title"
                        class="kt-input @error('title') kt-input-invalid @enderror"
                        value="{{ $currentTitle }}"
                        placeholder="Yazi basligini yazin"
                    >
                    @error('title')
                        <div class="text-xs text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="grid gap-3 rounded-3xl app-surface-card app-surface-card--soft p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <label class="kt-form-label font-normal text-mono mb-0" for="slug">Slug ve URL</label>
                        <span class="text-xs text-muted-foreground">URL stabilitesi icin sadece gerektiğinde degistirin.</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <input
                            id="slug"
                            name="slug"
                            class="kt-input flex-1 @error('slug') kt-input-invalid @enderror"
                            value="{{ $currentSlug }}"
                            placeholder="otomatik-olusturulur"
                        >

                        <button type="button" id="slug_regen" class="kt-btn kt-btn-light">Olustur</button>

                        <label class="kt-switch shrink-0" title="Otomatik slug">
                            <input
                                type="checkbox"
                                class="kt-switch"
                                id="slug_auto"
                                @checked($currentSlug === '')
                            >
                            <span class="kt-switch-slider"></span>
                        </label>
                    </div>

                    @error('slug')
                        <div class="text-xs text-danger">{{ $message }}</div>
                    @enderror

                    <div class="rounded-2xl app-surface-card px-4 py-3 text-sm text-muted-foreground">
                        URL onizleme:
                        <span class="font-medium text-foreground">{{ url('/blog') }}/<span id="url_slug_preview">{{ $currentSlug }}</span></span>
                    </div>

                    <div id="slugCheckHint" class="text-xs text-muted-foreground">
                        Slug girildiginde uygunluk kontrolu yapilir.
                    </div>
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between gap-3">
                        <label class="kt-form-label font-normal text-mono" for="excerpt">Ozet</label>
                        <span class="text-xs text-muted-foreground" data-blog-excerpt-count>{{ mb_strlen($currentExcerpt) }} karakter</span>
                    </div>
                    <textarea
                        id="excerpt"
                        name="excerpt"
                        rows="4"
                        class="kt-textarea @error('excerpt') kt-input-invalid @enderror"
                        placeholder="Liste gorunumu, arama sonucunda ozet ve paylasim kartlari icin kisa aciklama yazin"
                    >{{ $currentExcerpt }}</textarea>
                    @error('excerpt')
                        <div class="text-xs text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between gap-3">
                        <label class="kt-form-label font-normal text-mono" for="content_editor">Icerik</label>
                        <span class="text-xs text-muted-foreground">TinyMCE ile zengin icerik duzenleme</span>
                    </div>
                    <textarea
                        id="content_editor"
                        name="content"
                        class="kt-textarea @error('content') kt-input-invalid @enderror"
                    >{{ $currentContent }}</textarea>
                    @error('content')
                        <div class="text-xs text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="kt-card overflow-hidden">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">SEO ve Arama Onizlemesi</h3>
                    <div class="text-sm text-muted-foreground">
                        Meta alanlarini doldururken arama sonucunda nasil gorunecegini aninda izle.
                    </div>
                </div>
            </div>

            <div class="kt-card-content p-6 grid gap-6 lg:grid-cols-[minmax(0,1.15fr)_320px]">
                <div class="grid gap-5">
                    <div class="grid gap-2">
                        <div class="flex items-center justify-between gap-3">
                            <label class="kt-form-label font-normal text-mono">Meta Title</label>
                            <span class="text-xs text-muted-foreground" data-blog-meta-title-count>{{ mb_strlen($currentMetaTitle) }}/60 onerisi</span>
                        </div>
                        <input
                            name="meta_title"
                            class="kt-input @error('meta_title') kt-input-invalid @enderror"
                            value="{{ $currentMetaTitle }}"
                            placeholder="Arama sonucunda gosterilecek baslik"
                        >
                        @error('meta_title')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <div class="flex items-center justify-between gap-3">
                            <label class="kt-form-label font-normal text-mono">Meta Description</label>
                            <span class="text-xs text-muted-foreground" data-blog-meta-description-count>{{ mb_strlen($currentMetaDescription) }}/160 onerisi</span>
                        </div>
                        <textarea
                            name="meta_description"
                            rows="4"
                            class="kt-textarea @error('meta_description') kt-input-invalid @enderror"
                            placeholder="Arama sonucunda gosterilecek aciklama"
                        >{{ $currentMetaDescription }}</textarea>
                        @error('meta_description')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="kt-form-label font-normal text-mono">Meta Keywords</label>
                        <input
                            name="meta_keywords"
                            class="kt-input @error('meta_keywords') kt-input-invalid @enderror"
                            value="{{ $currentMetaKeywords }}"
                            placeholder="anahtar,kelimeler,seklinde"
                        >
                        @error('meta_keywords')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-4 self-start">
                    <div class="rounded-[28px] app-surface-card p-5">
                        <div class="text-[11px] uppercase tracking-[0.24em] text-muted-foreground">Arama Onizlemesi</div>
                        <div class="mt-4 grid gap-2">
                            <div class="text-base font-semibold leading-6 text-primary" data-blog-seo-preview-title>
                                {{ $previewTitle }}
                            </div>
                            <div class="text-sm text-success">
                                {{ url('/blog') }}/<span data-blog-seo-preview-slug>{{ $currentSlug ?: 'ornek-blog-yazisi' }}</span>
                            </div>
                            <div class="text-sm leading-6 text-muted-foreground" data-blog-seo-preview-description>
                                {{ $previewDescription }}
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl app-surface-card app-surface-card--soft p-4 text-sm text-muted-foreground">
                        Meta title icin 30-60, meta description icin 100-160 karakter araligi daha saglikli gorunur.
                    </div>
                </div>
            </div>
        </div>

        @if($isEdit)
            <div class="kt-card">
                <div class="kt-card-header py-5">
                    <div>
                        <h3 class="kt-card-title">Galeriler</h3>
                        <div class="text-sm text-muted-foreground">
                            Bu yaziya bagli galeri alanlarini slot bazinda yonetebilirsin.
                        </div>
                    </div>
                </div>

                <div class="kt-card-content p-6">
                    @include('admin.components.gallery-manager', [
                        'id' => 'blog-' . $blogPost->id,
                        'title' => 'Galeriler',
                        'routes' => [
                            'list' => route('admin.galleries.list'),
                            'index' => route('admin.blog.galleries.index', $blogPost),
                            'attach' => route('admin.blog.galleries.attach', $blogPost),
                            'detach' => route('admin.blog.galleries.detach', $blogPost),
                            'reorder' => route('admin.blog.galleries.reorder', $blogPost),
                        ],
                        'slots' => [
                            'main' => 'Ana',
                            'sidebar' => 'Sidebar',
                        ],
                    ])
                </div>
            </div>
        @endif
    </div>

    <div class="grid gap-6 self-start xl:sticky xl:top-6">
        <div class="kt-card overflow-hidden">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Yayin Akisi</h3>
                    <div class="text-sm text-muted-foreground">
                        Gorunurluk ve anasayfa durumunu tek panelden yonet.
                    </div>
                </div>
            </div>

            <div class="kt-card-content p-6 grid gap-4">
                <div class="rounded-3xl app-surface-card app-surface-card--soft p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="grid gap-1">
                            <div class="font-medium text-foreground">Yayin Durumu</div>
                            <div class="text-sm text-muted-foreground">Taslak veya yayinda olarak isaretleyin.</div>
                        </div>

                        <div class="flex items-center gap-3">
                            <input type="hidden" name="is_published" value="0">
                            <label class="kt-switch kt-switch-sm">
                                <input
                                    type="checkbox"
                                    class="kt-switch"
                                    id="blog_is_published"
                                    name="is_published"
                                    value="1"
                                    @checked($currentPublished)
                                >
                            </label>
                            <span
                                id="blog_publish_badge"
                                class="kt-badge kt-badge-sm {{ $currentPublished ? 'kt-badge-light-success' : 'kt-badge-light text-muted-foreground' }}"
                            >
                                {{ $currentPublished ? 'Yayinda' : 'Taslak' }}
                            </span>
                        </div>
                    </div>

                    @error('is_published')
                        <div class="mt-2 text-xs text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="rounded-3xl app-surface-card app-surface-card--soft p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="grid gap-1">
                            <div class="font-medium text-foreground">Anasayfa Vitrini</div>
                            <div class="text-sm text-muted-foreground">En fazla 5 yazi one cikarilabilir.</div>
                        </div>

                        <div class="flex items-center gap-3">
                            <input type="hidden" name="is_featured" value="0">
                            <label class="kt-switch kt-switch-sm">
                                <input
                                    type="checkbox"
                                    class="kt-switch"
                                    id="blog_is_featured"
                                    name="is_featured"
                                    value="1"
                                    @checked($currentFeatured)
                                >
                            </label>
                            <span
                                id="blog_featured_badge"
                                class="kt-badge kt-badge-sm {{ $currentFeatured ? 'kt-badge-light-success' : 'kt-badge-light text-muted-foreground' }}"
                            >
                                {{ $currentFeatured ? 'Anasayfada' : 'Kapali' }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-3 text-xs text-muted-foreground">
                        One cikan yazilarin yayinda olmasi, site tarafindaki akislarda daha tutarli gorunur.
                    </div>

                    @error('is_featured')
                        <div class="mt-2 text-xs text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="kt-card overflow-hidden">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Icerik Icgoruleri</h3>
                    <div class="text-sm text-muted-foreground">
                        Yazi yogunlugu ve SEO hazirligini canli takip et.
                    </div>
                </div>
            </div>

            <div class="kt-card-content p-6 grid gap-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-3xl app-surface-card p-4">
                        <div class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Kelime</div>
                        <div class="mt-2 text-2xl font-semibold text-foreground" data-blog-word-count>{{ $initialWordCount }} kelime</div>
                    </div>
                    <div class="rounded-3xl app-surface-card p-4">
                        <div class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Okuma</div>
                        <div class="mt-2 text-2xl font-semibold text-foreground" data-blog-read-time>{{ $initialReadTime }} dk</div>
                    </div>
                </div>

                <div class="rounded-3xl app-surface-card app-surface-card--soft p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-xs uppercase tracking-[0.18em] text-muted-foreground">SEO Tamamlilik</div>
                            <div class="mt-1 text-sm text-muted-foreground" data-blog-seo-summary>
                                {{ $initialSeoScore >= 80 ? 'SEO hazirligi guclu gorunuyor.' : ($initialSeoScore >= 50 ? 'Yazi iyi gidiyor, birkac alan daha guclendirilebilir.' : 'Ozet, meta alanlar ve gorsel tarafinda guclendirme gerekiyor.') }}
                            </div>
                        </div>
                        <div
                            class="text-3xl font-semibold {{ $initialSeoScore >= 80 ? 'text-success' : ($initialSeoScore >= 50 ? 'text-warning' : 'text-danger') }}"
                            data-blog-seo-score
                        >
                            %{{ $initialSeoScore }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="kt-card overflow-hidden">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Kategoriler</h3>
                    <div class="text-sm text-muted-foreground">
                        Yaziyi dogru kategorilerle etiketleyip kesfetmeyi kolaylastir.
                    </div>
                </div>
            </div>

            <div class="kt-card-content p-6 grid gap-3">
                <select
                    name="category_ids[]"
                    multiple
                    class="hidden"
                    data-kt-select="true"
                    data-kt-select-placeholder="Kategoriler"
                    data-kt-select-multiple="true"
                    data-kt-select-tags="false"
                    data-kt-select-config='{"showSelectedCount":true,"enableSelectAll":true,"selectAllText":"Tumunu Sec","clearAllText":"Temizle"}'
                >
                    @foreach($categoryOptions ?? [] as $option)
                        <option value="{{ $option['id'] }}" @selected(in_array($option['id'], $selectedCategoryIds))>
                            {{ $option['label'] }}
                        </option>
                    @endforeach
                </select>

                <div class="text-xs text-muted-foreground">
                    Birden fazla kategori secilebilir. Alt kategoriler, hiyerarsi korunarak listelenir.
                </div>

                @error('category_ids')
                    <div class="text-xs text-danger">{{ $message }}</div>
                @enderror
                @error('category_ids.*')
                    <div class="text-xs text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>

        @include('admin.components.featured-image-manager', [
            'title' => 'One Cikan Gorsel',
            'hint' => 'Dosya yukleyebilir veya medya kutuphanesinden secim yapabilirsiniz.',
            'name' => 'featured_image',
            'mediaIdName' => 'featured_media_id',
            'clearFlagName' => 'clear_featured_image',
            'currentMediaId' => $featuredMediaId,
            'currentUrl' => $currentFeaturedUrl,
        ])

        @error('featured_image')
            <div class="text-xs text-danger -mt-3">{{ $message }}</div>
        @enderror
        @error('featured_media_id')
            <div class="text-xs text-danger -mt-3">{{ $message }}</div>
        @enderror

        @if($isEdit)
            <div class="kt-card overflow-hidden">
                <div class="kt-card-header py-5">
                    <div>
                        <h3 class="kt-card-title">Kayit Bilgileri</h3>
                        <div class="text-sm text-muted-foreground">
                            Yazar, editor ve tarih bilgilerini hizlica takip et.
                        </div>
                    </div>
                </div>

                <div class="kt-card-content p-6 grid gap-3 text-sm">
                    <div class="rounded-2xl app-surface-card px-4 py-3">
                        <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">Kayit No</div>
                        <div class="mt-1 font-medium text-foreground">#{{ $blogPost->id }}</div>
                    </div>
                    <div class="rounded-2xl app-surface-card px-4 py-3">
                        <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">Yazar</div>
                        <div class="mt-1 font-medium text-foreground">{{ $blogPost->author?->name ?: 'Belirlenmedi' }}</div>
                    </div>
                    <div class="rounded-2xl app-surface-card px-4 py-3">
                        <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">Son Editor</div>
                        <div class="mt-1 font-medium text-foreground">{{ $blogPost->editor?->name ?: 'Belirlenmedi' }}</div>
                    </div>
                    <div class="rounded-2xl app-surface-card px-4 py-3">
                        <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">Olusturulma</div>
                        <div class="mt-1 font-medium text-foreground">{{ $blogPost->created_at?->format('d.m.Y H:i') ?: '-' }}</div>
                    </div>
                    <div class="rounded-2xl app-surface-card px-4 py-3">
                        <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">Son Guncelleme</div>
                        <div class="mt-1 font-medium text-foreground">{{ $blogPost->updated_at?->format('d.m.Y H:i') ?: '-' }}</div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-2xl app-surface-card px-4 py-3">
                            <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">Yayin</div>
                            <div class="mt-1 font-medium text-foreground">{{ $blogPost->published_at?->format('d.m.Y H:i') ?: 'Yok' }}</div>
                        </div>
                        <div class="rounded-2xl app-surface-card px-4 py-3">
                            <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">Anasayfa</div>
                            <div class="mt-1 font-medium text-foreground">{{ $blogPost->featured_at?->format('d.m.Y H:i') ?: 'Yok' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
