@php
    $isEdit = isset($blogPost) && $blogPost;
    $selectedCategoryIds = old('category_ids', $selectedCategoryIds ?? []);
    $featuredMediaId = old('featured_media_id', $featuredMediaId ?? null);
    $currentFeaturedUrl = $currentFeaturedUrl ?? null;
    $currentPublished = (bool) old('is_published', (bool) ($blogPost->is_published ?? false));
    $currentFeatured = (bool) old('is_featured', (bool) ($blogPost->is_featured ?? false));
    $storedTranslations = old('translations');

    if (!is_array($storedTranslations)) {
        $storedTranslations = collect($blogPost?->translations ?? [])
            ->mapWithKeys(fn ($translation) => [
                $translation->locale => [
                    'title' => $translation->title,
                    'slug' => $translation->slug,
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

<div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.6fr)_400px] gap-6">
    <div class="grid gap-6">
        @include('admin.components.localized-content-tabs', [
            'moduleKey' => 'blog',
            'title' => 'Blog İçerik Dilleri',
            'description' => 'Varsayılan dil ve ek diller için başlık, slug, içerik ve SEO alanlarını sekmelerden yönetin.',
            'urlBase' => url('/blog'),
            'defaultValues' => [
                'title' => old('title', $blogPost->title ?? ''),
                'slug' => old('slug', $blogPost->slug ?? ''),
                'excerpt' => old('excerpt', $blogPost->excerpt ?? ''),
                'content' => old('content', $blogPost->content ?? ''),
                'meta_title' => old('meta_title', $blogPost->meta_title ?? ''),
                'meta_description' => old('meta_description', $blogPost->meta_description ?? ''),
                'meta_keywords' => old('meta_keywords', $blogPost->meta_keywords ?? ''),
            ],
            'storedTranslations' => $storedTranslations,
            'fields' => [
                ['name' => 'title', 'id' => 'title', 'label' => 'Başlık', 'placeholder' => 'Blog başlığını yazın', 'slug_source' => true],
                ['name' => 'slug', 'id' => 'slug', 'type' => 'slug', 'label' => 'Slug ve URL'],
                ['name' => 'excerpt', 'type' => 'textarea', 'rows' => 4, 'label' => 'Özet', 'placeholder' => 'Liste ve arama sonuçlarında kullanılacak kısa açıklama'],
                ['name' => 'content', 'id' => 'content_editor', 'type' => 'editor', 'rows' => 10, 'label' => 'İçerik'],
                ['name' => 'meta_title', 'label' => 'Meta Başlık', 'placeholder' => 'Arama sonucunda görünecek başlık'],
                ['name' => 'meta_description', 'type' => 'textarea', 'rows' => 3, 'label' => 'Meta Açıklama'],
                ['name' => 'meta_keywords', 'label' => 'Meta Anahtar Kelimeler', 'placeholder' => 'anahtar, kelimeler, şeklinde'],
            ],
        ])

        @if($isEdit)
            <div class="kt-card">
                <div class="kt-card-header py-5">
                    <div>
                        <h3 class="kt-card-title">Galeriler</h3>
                        <div class="text-sm text-muted-foreground">Bu yazıya bağlı galeri alanlarını slot bazında yönetin.</div>
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
                    <h3 class="kt-card-title">Yayın Akışı</h3>
                    <div class="text-sm text-muted-foreground">Görünürlük ve anasayfa durumunu tek panelden yönetin.</div>
                </div>
            </div>
            <div class="kt-card-content p-6 grid gap-4">
                <div class="rounded-3xl app-surface-card app-surface-card--soft p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="font-medium text-foreground">Yayın Durumu</div>
                            <div class="text-sm text-muted-foreground">Taslak veya yayında olarak işaretleyin.</div>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="hidden" name="is_published" value="0">
                            <label class="kt-switch kt-switch-sm">
                                <input type="checkbox" class="kt-switch" id="blog_is_published" name="is_published" value="1" @checked($currentPublished)>
                            </label>
                            <span id="blog_publish_badge" class="kt-badge kt-badge-sm {{ $currentPublished ? 'kt-badge-light-success' : 'kt-badge-light text-muted-foreground' }}">
                                {{ $currentPublished ? 'Yayında' : 'Taslak' }}
                            </span>
                        </div>
                    </div>
                    @error('is_published')<div class="mt-2 text-xs text-danger">{{ $message }}</div>@enderror
                </div>

                <div class="rounded-3xl app-surface-card app-surface-card--soft p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="font-medium text-foreground">Anasayfa Vitrini</div>
                            <div class="text-sm text-muted-foreground">En fazla 5 yazı öne çıkarılabilir.</div>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="hidden" name="is_featured" value="0">
                            <label class="kt-switch kt-switch-sm">
                                <input type="checkbox" class="kt-switch" id="blog_is_featured" name="is_featured" value="1" @checked($currentFeatured)>
                            </label>
                            <span id="blog_featured_badge" class="kt-badge kt-badge-sm {{ $currentFeatured ? 'kt-badge-light-success' : 'kt-badge-light text-muted-foreground' }}">
                                {{ $currentFeatured ? 'Anasayfada' : 'Kapalı' }}
                            </span>
                        </div>
                    </div>
                    @error('is_featured')<div class="mt-2 text-xs text-danger">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        <div class="kt-card overflow-hidden">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Kategoriler</h3>
                    <div class="text-sm text-muted-foreground">Yazıyı doğru kategorilerle ilişkilendirin.</div>
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
                    data-kt-select-config='{"showSelectedCount":true,"enableSelectAll":true,"selectAllText":"Tümünü Seç","clearAllText":"Temizle"}'
                >
                    @foreach($categoryOptions ?? [] as $option)
                        <option value="{{ $option['id'] }}" @selected(in_array($option['id'], $selectedCategoryIds))>{{ $option['label'] }}</option>
                    @endforeach
                </select>
                @error('category_ids')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                @error('category_ids.*')<div class="text-xs text-danger">{{ $message }}</div>@enderror
            </div>
        </div>

        @include('admin.components.featured-image-manager', [
            'title' => 'Öne Çıkan Görsel',
            'hint' => 'Dosya yükleyebilir veya medya kütüphanesinden seçim yapabilirsiniz.',
            'name' => 'featured_image',
            'fileName' => 'featured_image',
            'mediaIdName' => 'featured_media_id',
            'clearFlagName' => 'clear_featured_image',
            'currentMediaId' => $featuredMediaId,
            'currentUrl' => $currentFeaturedUrl,
        ])

        @error('featured_image')<div class="text-xs text-danger -mt-3">{{ $message }}</div>@enderror
        @error('featured_media_id')<div class="text-xs text-danger -mt-3">{{ $message }}</div>@enderror

        @if($isEdit)
            <div class="rounded-3xl app-surface-card p-5 text-sm text-muted-foreground">
                <div class="font-medium text-foreground">Kayıt Bilgileri</div>
                <div class="mt-3 grid gap-2">
                    <div>No: #{{ $blogPost->id }}</div>
                    <div>Yazar: {{ $blogPost->author?->name ?: 'Belirlenmedi' }}</div>
                    <div>Son editör: {{ $blogPost->editor?->name ?: 'Belirlenmedi' }}</div>
                    <div>Oluşturulma: {{ $blogPost->created_at?->format('d.m.Y H:i') ?: '-' }}</div>
                    <div>Son güncelleme: {{ $blogPost->updated_at?->format('d.m.Y H:i') ?: '-' }}</div>
                </div>
            </div>
        @endif
    </div>
</div>
