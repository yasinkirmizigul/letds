@php
    $isEdit = filled($page?->id);
    $title = old('title', $page->title ?? '');
    $slug = old('slug', $page->slug ?? '');
    $heroKicker = old('hero_kicker', $page->hero_kicker ?? '');
    $excerpt = old('excerpt', $page->excerpt ?? '');
    $content = old('content', $page->content ?? '');
    $iconClass = old('icon_class', $page->icon_class ?? 'ki-filled ki-abstract-26');
    $metaTitle = old('meta_title', $page->meta_title ?? '');
    $metaDescription = old('meta_description', $page->meta_description ?? '');
    $metaKeywords = old('meta_keywords', $page->meta_keywords ?? '');
    $showFaqs = (bool) old('show_faqs', $page->show_faqs ?? false);
    $showCounters = (bool) old('show_counters', $page->show_counters ?? false);
    $isFeatured = (bool) old('is_featured', $page->is_featured ?? false);
    $isActive = (bool) old('is_active', $page->is_active ?? true);
    $sortOrder = old('sort_order', $page->sort_order ?? 0);
    $publishedAt = old('published_at', optional($page?->published_at)->format('Y-m-d H:i'));
    $featuredUrl = $page?->featuredUrl();
@endphp

<div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.6fr)_400px]">
    <div class="grid gap-6">
        <div class="kt-card overflow-hidden">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">İçerik Omurgası</h3>
                    <div class="text-sm text-muted-foreground">
                        Başlık, slug, vurgu metni ve ana gövdeyi tek akışta kurgula.
                    </div>
                </div>
            </div>

            <div class="kt-card-content grid gap-6 p-6">
                <div class="grid gap-2">
                    <label class="kt-form-label" for="title">Sayfa Başlığı</label>
                    <input id="title" name="title" class="kt-input @error('title') kt-input-invalid @enderror" value="{{ $title }}" placeholder="Örn. Kurumsal Web Tasarım Hizmeti">
                    @error('title')
                        <div class="text-xs text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="grid gap-3 rounded-3xl app-surface-card app-surface-card--soft p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <label class="kt-form-label mb-0" for="slug">Bağlantı Adresi</label>
                        <span class="text-xs text-muted-foreground">Başlıktan otomatik üretilebilir.</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <input id="slug" name="slug" class="kt-input flex-1 @error('slug') kt-input-invalid @enderror" value="{{ $slug }}" placeholder="otomatik-olusturulur">
                        <button type="button" id="slug_regen" class="kt-btn kt-btn-light">Oluştur</button>
                        <label class="kt-switch shrink-0">
                            <input type="checkbox" id="slug_auto" class="kt-switch" @checked($slug === '')>
                            <span class="kt-switch-slider"></span>
                        </label>
                    </div>

                    <div class="rounded-2xl app-surface-card px-4 py-3 text-sm text-muted-foreground">
                        URL önizleme:
                        <span class="font-medium text-foreground">{{ url('/') }}/<span id="url_slug_preview">{{ $slug }}</span></span>
                    </div>

                    @error('slug')
                        <div class="text-xs text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="grid gap-2">
                    <label class="kt-form-label" for="hero_kicker">Hero Üst Başlık</label>
                    <input id="hero_kicker" name="hero_kicker" class="kt-input @error('hero_kicker') kt-input-invalid @enderror" value="{{ $heroKicker }}" placeholder="Örn. Dönüşüm Odaklı Dijital Deneyim">
                    @error('hero_kicker')
                        <div class="text-xs text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="grid gap-2">
                    <label class="kt-form-label" for="excerpt">Kısa Özet</label>
                    <textarea id="excerpt" name="excerpt" rows="4" class="kt-textarea @error('excerpt') kt-input-invalid @enderror" placeholder="Kartlarda, hero alanında ve arama motoru önizlemesinde kullanılacak kısa açıklama.">{{ $excerpt }}</textarea>
                    @error('excerpt')
                        <div class="text-xs text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="grid gap-2">
                    <label class="kt-form-label" for="content_editor">Ana İçerik</label>
                    <textarea id="content_editor" name="content" class="kt-textarea @error('content') kt-input-invalid @enderror">{{ $content }}</textarea>
                    @error('content')
                        <div class="text-xs text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="kt-card overflow-hidden">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">SEO ve İçerik Önizlemesi</h3>
                    <div class="text-sm text-muted-foreground">
                        Meta alanlarını doldururken arama görünümünü eş zamanlı güçlendir.
                    </div>
                </div>
            </div>

            <div class="kt-card-content grid gap-6 p-6 lg:grid-cols-[minmax(0,1.15fr)_320px]">
                <div class="grid gap-5">
                    <div class="grid gap-2">
                        <label class="kt-form-label">Meta Başlık</label>
                        <input name="meta_title" class="kt-input @error('meta_title') kt-input-invalid @enderror" value="{{ $metaTitle }}" placeholder="Arama sonucunda görünecek başlık">
                        @error('meta_title')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="kt-form-label">Meta Açıklama</label>
                        <textarea name="meta_description" rows="4" class="kt-textarea @error('meta_description') kt-input-invalid @enderror" placeholder="Arama sonucunda görülecek açıklama">{{ $metaDescription }}</textarea>
                        @error('meta_description')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="kt-form-label">Meta Anahtar Kelimeler</label>
                        <input name="meta_keywords" class="kt-input @error('meta_keywords') kt-input-invalid @enderror" value="{{ $metaKeywords }}" placeholder="web tasarım, marka sitesi, landing page">
                        @error('meta_keywords')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-4 self-start">
                    <div class="rounded-[28px] app-surface-card p-5">
                        <div class="text-[11px] uppercase tracking-[0.24em] text-muted-foreground">Arama Önizlemesi</div>
                        <div class="mt-4 grid gap-2">
                            <div class="text-base font-semibold leading-6 text-primary" data-page-seo-preview-title>{{ $metaTitle ?: ($title ?: 'Meta başlık burada görünecek') }}</div>
                            <div class="text-sm text-success">{{ url('/') }}/<span data-page-seo-preview-slug>{{ $slug ?: 'ornek-sayfa' }}</span></div>
                            <div class="text-sm leading-6 text-muted-foreground" data-page-seo-preview-description>{{ $metaDescription ?: ($excerpt ?: 'Meta açıklama burada görünecek.') }}</div>
                        </div>
                    </div>

                    <div class="rounded-3xl app-surface-card app-surface-card--soft p-4 text-sm text-muted-foreground">
                        Bu sayfa menüye bağlandığında başlık, özet ve görsel alanları otomatik olarak ön yüzde kullanılabilir.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 self-start xl:sticky xl:top-6">
        <div class="kt-card overflow-hidden">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Yayın Kontrolü</h3>
                    <div class="text-sm text-muted-foreground">
                        Sayfanın vitrin durumunu ve yardımcı bloklarını buradan yönet.
                    </div>
                </div>
            </div>

            <div class="kt-card-content grid gap-4 p-6">
                <div class="rounded-3xl app-surface-card app-surface-card--soft p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="font-medium">Aktiflik</div>
                            <div class="text-sm text-muted-foreground">Sayfa ön yüzde erişilebilir olsun mu?</div>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="hidden" name="is_active" value="0">
                            <label class="kt-switch kt-switch-sm">
                                <input type="checkbox" name="is_active" value="1" id="page_is_active" class="kt-switch" @checked($isActive)>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl app-surface-card app-surface-card--soft p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="font-medium">Öne Çıkan Sayfa</div>
                            <div class="text-sm text-muted-foreground">Ana sayfa kartlarında görünmesini sağlar.</div>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="hidden" name="is_featured" value="0">
                            <label class="kt-switch kt-switch-sm">
                                <input type="checkbox" name="is_featured" value="1" id="page_is_featured" class="kt-switch" @checked($isFeatured)>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="grid gap-3 rounded-3xl app-surface-card app-surface-card--soft p-4">
                    <label class="flex items-start gap-3">
                        <input type="hidden" name="show_faqs" value="0">
                        <input type="checkbox" name="show_faqs" value="1" class="kt-checkbox mt-1" @checked($showFaqs)>
                        <span>
                            <span class="block font-medium text-foreground">SSS Bölümünü Aç</span>
                            <span class="text-sm text-muted-foreground">Bu sayfaya bağlı SSS kayıtları ön yüzde görünsün.</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3">
                        <input type="hidden" name="show_counters" value="0">
                        <input type="checkbox" name="show_counters" value="1" class="kt-checkbox mt-1" @checked($showCounters)>
                        <span>
                            <span class="block font-medium text-foreground">Sayaç Bölümünü Aç</span>
                            <span class="text-sm text-muted-foreground">Bu sayfaya bağlı dinamik metrikleri göster.</span>
                        </span>
                    </label>
                </div>

                <div class="grid gap-2">
                    <label class="kt-form-label">Yayın Tarihi</label>
                    <input
                        type="text"
                        name="published_at"
                        class="kt-input @error('published_at') kt-input-invalid @enderror"
                        placeholder="GG.AA.YYYY SS:DD"
                        value="{{ $publishedAt }}"
                        data-app-date-picker="true"
                        data-app-date-mode="datetime"
                        data-kt-date-picker="true"
                        data-kt-date-picker-input-mode="true"
                        data-kt-date-picker-locale="tr-TR"
                        data-kt-date-picker-date-format="DD.MM.YYYY HH:mm"
                        data-initial-value="{{ $publishedAt }}"
                    >
                    @error('published_at')
                        <div class="text-xs text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="grid gap-2">
                    <label class="kt-form-label">Sıralama</label>
                    <input type="number" name="sort_order" class="kt-input @error('sort_order') kt-input-invalid @enderror" value="{{ $sortOrder }}" min="0">
                    @error('sort_order')
                        <div class="text-xs text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="kt-card overflow-hidden">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">İkon ve Vurgu</h3>
                    <div class="text-sm text-muted-foreground">
                        Kartlarda ve hero alanında kullanılacak ikonu belirle.
                    </div>
                </div>
            </div>

            <div class="kt-card-content grid gap-4 p-6">
                <div class="grid gap-2">
                    <label class="kt-form-label">İkon Sınıfı</label>
                    <input name="icon_class" class="kt-input @error('icon_class') kt-input-invalid @enderror" value="{{ $iconClass }}" placeholder="ki-filled ki-abstract-26">
                    @error('icon_class')
                        <div class="text-xs text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="flex flex-wrap gap-2">
                    @foreach(['ki-filled ki-rocket', 'ki-filled ki-abstract-26', 'ki-filled ki-badge', 'ki-filled ki-element-11', 'ki-filled ki-star'] as $suggestedIcon)
                        <button type="button" class="kt-btn kt-btn-sm kt-btn-light js-icon-chip" data-icon-value="{{ $suggestedIcon }}">
                            <i class="{{ $suggestedIcon }}"></i>
                            {{ $suggestedIcon }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        @include('admin.components.featured-image-manager', [
            'title' => 'Öne Çıkan Görsel',
            'hint' => 'Hero alanı, kart görünümü ve paylaşım önizlemesi için kullanılır.',
            'fileName' => 'featured_image',
            'mediaIdName' => 'featured_media_id',
            'clearFlagName' => 'clear_featured_image',
            'currentMediaId' => old('featured_media_id', $page->featured_media_id ?? null),
            'currentUrl' => $featuredUrl,
        ])

        @if($isEdit)
            <div class="rounded-3xl app-surface-card p-5 text-sm text-muted-foreground">
                <div class="font-medium text-foreground">Kayıt Bilgileri</div>
                <div class="mt-3 grid gap-2">
                    <div>No: #{{ $page->id }}</div>
                    <div>Oluşturulma: {{ optional($page->created_at)->format('d.m.Y H:i') ?: '-' }}</div>
                    <div>Son Güncelleme: {{ optional($page->updated_at)->format('d.m.Y H:i') ?: '-' }}</div>
                </div>
            </div>
        @endif
    </div>
</div>
