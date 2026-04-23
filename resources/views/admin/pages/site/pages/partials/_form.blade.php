@php
    $isEdit = filled($page?->id);
    $iconClass = old('icon_class', $page->icon_class ?? 'ki-filled ki-abstract-26');
    $showFaqs = (bool) old('show_faqs', $page->show_faqs ?? false);
    $showCounters = (bool) old('show_counters', $page->show_counters ?? false);
    $isFeatured = (bool) old('is_featured', $page->is_featured ?? false);
    $isActive = (bool) old('is_active', $page->is_active ?? true);
    $sortOrder = old('sort_order', $page->sort_order ?? 0);
    $publishedAt = old('published_at', optional($page?->published_at)->format('Y-m-d H:i'));
    $featuredUrl = $page?->featuredUrl();
    $storedTranslations = old('translations');

    if (!is_array($storedTranslations)) {
        $storedTranslations = collect($pageTranslations ?? [])
            ->mapWithKeys(fn ($translation) => [
                $translation->locale => [
                    'title' => $translation->title,
                    'slug' => $translation->slug,
                    'hero_kicker' => $translation->hero_kicker,
                    'excerpt' => $translation->excerpt,
                    'content' => $translation->content,
                    'meta_title' => $translation->meta_title,
                    'meta_description' => $translation->meta_description,
                    'meta_keywords' => $translation->meta_keywords,
                ],
            ])
            ->toArray();
    }
@endphp

<div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.6fr)_400px]">
    <div class="grid gap-6">
        @include('admin.components.localized-content-tabs', [
            'moduleKey' => 'site_page',
            'title' => 'Sayfa İçerik Dilleri',
            'description' => 'Varsayılan dil ve ek diller için sayfa içeriğini aynı seviyedeki sekmelerden yönetin.',
            'urlBase' => url('/'),
            'defaultValues' => [
                'title' => old('title', $page->title ?? ''),
                'slug' => old('slug', $page->slug ?? ''),
                'hero_kicker' => old('hero_kicker', $page->hero_kicker ?? ''),
                'excerpt' => old('excerpt', $page->excerpt ?? ''),
                'content' => old('content', $page->content ?? ''),
                'meta_title' => old('meta_title', $page->meta_title ?? ''),
                'meta_description' => old('meta_description', $page->meta_description ?? ''),
                'meta_keywords' => old('meta_keywords', $page->meta_keywords ?? ''),
            ],
            'storedTranslations' => $storedTranslations,
            'fields' => [
                ['name' => 'title', 'id' => 'title', 'label' => 'Sayfa Başlığı', 'placeholder' => 'Örn. Kurumsal Web Tasarım Hizmeti', 'slug_source' => true],
                ['name' => 'slug', 'id' => 'slug', 'type' => 'slug', 'label' => 'Bağlantı Adresi'],
                ['name' => 'hero_kicker', 'label' => 'Hero Üst Başlık'],
                ['name' => 'excerpt', 'id' => 'excerpt', 'type' => 'textarea', 'rows' => 4, 'label' => 'Kısa Özet'],
                ['name' => 'content', 'id' => 'content_editor', 'type' => 'editor', 'rows' => 10, 'label' => 'Ana İçerik'],
                ['name' => 'meta_title', 'label' => 'Meta Başlık'],
                ['name' => 'meta_description', 'type' => 'textarea', 'rows' => 3, 'label' => 'Meta Açıklama'],
                ['name' => 'meta_keywords', 'label' => 'Meta Anahtar Kelimeler'],
            ],
        ])
    </div>

    <div class="grid gap-6 self-start xl:sticky xl:top-6">
        <div class="kt-card overflow-hidden">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Yayın Kontrolü</h3>
                    <div class="text-sm text-muted-foreground">Sayfanın görünürlüğünü ve yardımcı bloklarını yönetin.</div>
                </div>
            </div>
            <div class="kt-card-content grid gap-4 p-6">
                <label class="flex items-start justify-between gap-3 rounded-3xl app-surface-card app-surface-card--soft p-4">
                    <span>
                        <span class="block font-medium">Aktiflik</span>
                        <span class="text-sm text-muted-foreground">Sayfa ön yüzde erişilebilir olsun mu?</span>
                    </span>
                    <span>
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" id="page_is_active" class="kt-switch" @checked($isActive)>
                    </span>
                </label>

                <label class="flex items-start justify-between gap-3 rounded-3xl app-surface-card app-surface-card--soft p-4">
                    <span>
                        <span class="block font-medium">Öne Çıkan Sayfa</span>
                        <span class="text-sm text-muted-foreground">Ana sayfa kartlarında görünmesini sağlar.</span>
                    </span>
                    <span>
                        <input type="hidden" name="is_featured" value="0">
                        <input type="checkbox" name="is_featured" value="1" id="page_is_featured" class="kt-switch" @checked($isFeatured)>
                    </span>
                </label>

                <label class="flex items-start gap-3 rounded-3xl app-surface-card app-surface-card--soft p-4">
                    <input type="hidden" name="show_faqs" value="0">
                    <input type="checkbox" name="show_faqs" value="1" class="kt-checkbox mt-1" @checked($showFaqs)>
                    <span>
                        <span class="block font-medium text-foreground">SSS Bölümünü Aç</span>
                        <span class="text-sm text-muted-foreground">Bu sayfaya bağlı SSS kayıtları ön yüzde görünsün.</span>
                    </span>
                </label>

                <label class="flex items-start gap-3 rounded-3xl app-surface-card app-surface-card--soft p-4">
                    <input type="hidden" name="show_counters" value="0">
                    <input type="checkbox" name="show_counters" value="1" class="kt-checkbox mt-1" @checked($showCounters)>
                    <span>
                        <span class="block font-medium text-foreground">Sayaç Bölümünü Aç</span>
                        <span class="text-sm text-muted-foreground">Bu sayfaya bağlı dinamik metrikleri göster.</span>
                    </span>
                </label>

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
                    @error('published_at')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                </div>

                <div class="grid gap-2">
                    <label class="kt-form-label">Sıralama</label>
                    <input type="number" name="sort_order" class="kt-input @error('sort_order') kt-input-invalid @enderror" value="{{ $sortOrder }}" min="0">
                    @error('sort_order')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        <div class="kt-card overflow-hidden">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">İkon ve Vurgu</h3>
                    <div class="text-sm text-muted-foreground">Kartlarda ve hero alanında kullanılacak ikonu belirleyin.</div>
                </div>
            </div>
            <div class="kt-card-content grid gap-4 p-6">
                <div class="grid gap-2">
                    <label class="kt-form-label">İkon Sınıfı</label>
                    <input name="icon_class" class="kt-input @error('icon_class') kt-input-invalid @enderror" value="{{ $iconClass }}" placeholder="ki-filled ki-abstract-26">
                    @error('icon_class')<div class="text-xs text-danger">{{ $message }}</div>@enderror
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
