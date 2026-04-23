@php
    $product = $product ?? null;
    $isEdit = filled($product?->id);
    $currentStatus = old('status', $product->status ?? \App\Models\Admin\Product\Product::STATUS_APPOINTMENT_PENDING);
    $currentFeatured = (bool) old('is_featured', (bool) ($product->is_featured ?? false));
    $currentActive = (bool) old('is_active', $product->is_active ?? true);
    $selectedCategoryIds = old('category_ids', $selectedCategoryIds ?? []);
    $featuredMediaId = old('featured_media_id', $featuredMediaId ?? null);
    $currentFeaturedUrl = $product?->featuredMediaUrl() ?? $product?->featured_image_url;
    $statusOptions = $statusOptions ?? \App\Models\Admin\Product\Product::statusOptionsSorted();
    $currentStatusMeta = $statusOptions[$currentStatus] ?? ($statusOptions[\App\Models\Admin\Product\Product::STATUS_APPOINTMENT_PENDING] ?? null);
    $storedTranslations = old('translations');

    if (!is_array($storedTranslations)) {
        $storedTranslations = collect($product?->translations ?? [])
            ->mapWithKeys(fn ($translation) => [
                $translation->locale => [
                    'title' => $translation->title,
                    'slug' => $translation->slug,
                    'content' => $translation->content,
                    'meta_title' => $translation->meta_title,
                    'meta_description' => $translation->meta_description,
                    'meta_keywords' => $translation->meta_keywords,
                ],
            ])
            ->toArray();
    }
@endphp

<div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.55fr)_400px] gap-6">
    <div class="grid gap-6">
        @include('admin.components.localized-content-tabs', [
            'moduleKey' => 'product',
            'title' => 'Ürün İçerik Dilleri',
            'description' => 'Varsayılan dil ve ek diller için ürün içeriğini, slug bilgisini ve SEO alanlarını sekmelerden yönetin.',
            'urlBase' => url('/products'),
            'defaultValues' => [
                'title' => old('title', $product->title ?? ''),
                'slug' => old('slug', $product->slug ?? ''),
                'content' => old('content', $product->content ?? ''),
                'meta_title' => old('meta_title', $product->meta_title ?? ''),
                'meta_description' => old('meta_description', $product->meta_description ?? ''),
                'meta_keywords' => old('meta_keywords', $product->meta_keywords ?? ''),
            ],
            'storedTranslations' => $storedTranslations,
            'fields' => [
                ['name' => 'title', 'id' => 'title', 'label' => 'Başlık', 'placeholder' => 'Ürün başlığını yazın', 'slug_source' => true],
                ['name' => 'slug', 'id' => 'slug', 'type' => 'slug', 'label' => 'Slug ve URL'],
                ['name' => 'content', 'id' => 'content_editor', 'type' => 'editor', 'rows' => 10, 'label' => 'Ürün Detayı'],
                ['name' => 'meta_title', 'label' => 'Meta Başlık'],
                ['name' => 'meta_description', 'type' => 'textarea', 'rows' => 3, 'label' => 'Meta Açıklama'],
                ['name' => 'meta_keywords', 'label' => 'Meta Anahtar Kelimeler'],
            ],
        ])

        <div class="kt-card overflow-hidden">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Ticari Bilgiler</h3>
                    <div class="text-sm text-muted-foreground">Fiyat, stok, barkod ve lojistik alanlarını yönetin.</div>
                </div>
            </div>
            <div class="kt-card-content p-6 grid gap-5">
                <div class="grid gap-5 md:grid-cols-3">
                    <div class="grid gap-2">
                        <label class="kt-form-label" for="sku">SKU</label>
                        <input id="sku" name="sku" class="kt-input @error('sku') kt-input-invalid @enderror" value="{{ old('sku', $product->sku ?? '') }}" placeholder="ABC-123">
                        @error('sku')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid gap-2">
                        <label class="kt-form-label" for="barcode">Barkod</label>
                        <input id="barcode" name="barcode" class="kt-input @error('barcode') kt-input-invalid @enderror" value="{{ old('barcode', $product->barcode ?? '') }}" placeholder="8690000000000">
                        @error('barcode')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid gap-2">
                        <label class="kt-form-label" for="brand">Marka</label>
                        <input id="brand" name="brand" class="kt-input @error('brand') kt-input-invalid @enderror" value="{{ old('brand', $product->brand ?? '') }}" placeholder="Marka adı">
                        @error('brand')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="grid gap-5 md:grid-cols-4">
                    <div class="grid gap-2">
                        <label class="kt-form-label" for="price">Fiyat</label>
                        <input id="price" name="price" type="number" step="0.01" min="0" class="kt-input @error('price') kt-input-invalid @enderror" value="{{ old('price', $product->price ?? '') }}" placeholder="0.00">
                        @error('price')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid gap-2">
                        <label class="kt-form-label" for="sale_price">İndirimli Fiyat</label>
                        <input id="sale_price" name="sale_price" type="number" step="0.01" min="0" class="kt-input @error('sale_price') kt-input-invalid @enderror" value="{{ old('sale_price', $product->sale_price ?? '') }}" placeholder="0.00">
                        @error('sale_price')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid gap-2">
                        <label class="kt-form-label" for="currency">Para Birimi</label>
                        <input id="currency" name="currency" maxlength="3" class="kt-input @error('currency') kt-input-invalid @enderror" value="{{ old('currency', $product->currency ?? 'TRY') }}" placeholder="TRY">
                        @error('currency')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid gap-2">
                        <label class="kt-form-label" for="vat_rate">KDV (%)</label>
                        <input id="vat_rate" name="vat_rate" type="number" min="0" max="100" class="kt-input @error('vat_rate') kt-input-invalid @enderror" value="{{ old('vat_rate', $product->vat_rate ?? '') }}" placeholder="20">
                        @error('vat_rate')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="grid gap-5 md:grid-cols-5">
                    @foreach([
                        'stock' => ['Stok', 'number', '0'],
                        'weight' => ['Ağırlık (kg)', 'number', '0.000'],
                        'width' => ['En (cm)', 'number', '0.000'],
                        'height' => ['Boy (cm)', 'number', '0.000'],
                        'length' => ['Derinlik (cm)', 'number', '0.000'],
                    ] as $field => [$label, $type, $placeholder])
                        <div class="grid gap-2">
                            <label class="kt-form-label" for="{{ $field }}">{{ $label }}</label>
                            <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" min="0" step="{{ $field === 'stock' ? '1' : '0.001' }}" class="kt-input @error($field) kt-input-invalid @enderror" value="{{ old($field, $product->{$field} ?? '') }}" placeholder="{{ $placeholder }}">
                            @error($field)<div class="text-xs text-danger">{{ $message }}</div>@enderror
                        </div>
                    @endforeach
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div class="grid gap-2">
                        <label class="kt-form-label" for="sort_order">Liste Sırası</label>
                        <input id="sort_order" name="sort_order" type="number" min="0" class="kt-input @error('sort_order') kt-input-invalid @enderror" value="{{ old('sort_order', $product->sort_order ?? 0) }}">
                        @error('sort_order')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid gap-2">
                        <label class="kt-form-label" for="appointment_id">Randevu ID</label>
                        <input id="appointment_id" name="appointment_id" type="number" min="1" class="kt-input @error('appointment_id') kt-input-invalid @enderror" value="{{ old('appointment_id', $product->appointment_id ?? '') }}" placeholder="Opsiyonel">
                        @error('appointment_id')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        @if($isEdit)
            @includeIf('admin.pages.products.partials._gallery', ['product' => $product])
        @endif
    </div>

    <div class="grid gap-6 self-start xl:sticky xl:top-6">
        <div class="kt-card overflow-hidden">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Durum ve Vitrin</h3>
                    <div class="text-sm text-muted-foreground">Workflow, aktiflik ve anasayfa vitrini ayarlarını yönetin.</div>
                </div>
            </div>
            <div class="kt-card-content p-6 grid gap-4">
                <div class="rounded-3xl app-surface-card app-surface-card--soft p-4">
                    <div class="grid gap-3">
                        <div class="flex items-center justify-between gap-3">
                            <div class="font-medium text-foreground">Workflow Durumu</div>
                            <span id="product_status_badge" class="{{ $currentStatusMeta['badge'] ?? 'kt-badge kt-badge-sm kt-badge-light' }}">{{ $currentStatusMeta['label'] ?? $currentStatus }}</span>
                        </div>
                        <select id="product_status" name="status" class="kt-select w-full @error('status') kt-input-invalid @enderror" data-kt-select="true" data-kt-select-placeholder="Durum">
                            @foreach($statusOptions as $key => $option)
                                <option value="{{ $key }}" @selected($currentStatus === $key)>{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                        <div class="text-xs text-muted-foreground" data-product-status-hint>Workflow seçimi ürünün operasyonel aşamasını netleştirir.</div>
                        @error('status')<div class="text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                </div>

                @foreach([
                    'is_featured' => ['product_is_featured', 'product_featured_badge', 'Anasayfa Vitrini', 'En fazla 5 ürün öne çıkarılabilir.', $currentFeatured, 'Anasayfada', 'Kapalı'],
                    'is_active' => ['product_is_active', 'product_active_badge', 'Aktiflik', 'Listeleme ve operasyonel kullanım kontrolü.', $currentActive, 'Aktif', 'Pasif'],
                ] as $name => [$id, $badgeId, $label, $hint, $checked, $onText, $offText])
                    <div class="rounded-3xl app-surface-card app-surface-card--soft p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="font-medium text-foreground">{{ $label }}</div>
                                <div class="text-sm text-muted-foreground">{{ $hint }}</div>
                            </div>
                            <div class="flex items-center gap-3">
                                <input type="hidden" name="{{ $name }}" value="0">
                                <label class="kt-switch kt-switch-sm">
                                    <input type="checkbox" class="kt-switch" id="{{ $id }}" name="{{ $name }}" value="1" @checked($checked)>
                                </label>
                                <span id="{{ $badgeId }}" class="kt-badge kt-badge-sm {{ $checked ? 'kt-badge-light-success' : 'kt-badge-light text-muted-foreground' }}">
                                    {{ $checked ? $onText : $offText }}
                                </span>
                            </div>
                        </div>
                        @error($name)<div class="mt-2 text-xs text-danger">{{ $message }}</div>@enderror
                    </div>
                @endforeach
            </div>
        </div>

        <div class="kt-card overflow-hidden">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Kategoriler</h3>
                    <div class="text-sm text-muted-foreground">Ürünü doğru kategorilerle ilişkilendirin.</div>
                </div>
            </div>
            <div class="kt-card-content p-6 grid gap-3">
                <select name="category_ids[]" multiple class="hidden" data-kt-select="true" data-kt-select-placeholder="Kategoriler" data-kt-select-multiple="true" data-kt-select-tags="false" data-kt-select-config='{"showSelectedCount":true,"enableSelectAll":true,"selectAllText":"Tümünü Seç","clearAllText":"Temizle"}'>
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
            'fileName' => 'featured_image',
            'mediaIdName' => 'featured_media_id',
            'clearFlagName' => 'clear_featured_image',
            'currentMediaId' => $featuredMediaId,
            'currentUrl' => $currentFeaturedUrl,
        ])

        @if($isEdit)
            <div class="rounded-3xl app-surface-card p-5 text-sm text-muted-foreground">
                <div class="font-medium text-foreground">Kayıt Bilgisi</div>
                <div class="mt-3 grid gap-2">
                    <div>No: #{{ $product->id }}</div>
                    <div>Oluşturulma: {{ $product->created_at?->format('d.m.Y H:i') ?: '-' }}</div>
                    <div>Son güncelleme: {{ $product->updated_at?->format('d.m.Y H:i') ?: '-' }}</div>
                </div>
            </div>
        @endif
    </div>
</div>
