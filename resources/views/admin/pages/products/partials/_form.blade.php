@php
    $product = $product ?? null;
    $isEdit = filled($product?->id);

    $currentTitle = old('title', $product->title ?? '');
    $currentSlug = old('slug', $product->slug ?? '');
    $currentContent = old('content', $product->content ?? '');
    $currentSku = old('sku', $product->sku ?? '');
    $currentBarcode = old('barcode', $product->barcode ?? '');
    $currentBrand = old('brand', $product->brand ?? '');
    $currentPrice = old('price', $product->price ?? '');
    $currentSalePrice = old('sale_price', $product->sale_price ?? '');
    $currentCurrency = old('currency', $product->currency ?? 'TRY');
    $currentVatRate = old('vat_rate', $product->vat_rate ?? '');
    $currentStock = old('stock', $product->stock ?? '');
    $currentWeight = old('weight', $product->weight ?? '');
    $currentWidth = old('width', $product->width ?? '');
    $currentHeight = old('height', $product->height ?? '');
    $currentLength = old('length', $product->length ?? '');
    $currentSortOrder = old('sort_order', $product->sort_order ?? 0);
    $currentMetaTitle = old('meta_title', $product->meta_title ?? '');
    $currentMetaDescription = old('meta_description', $product->meta_description ?? '');
    $currentMetaKeywords = old('meta_keywords', $product->meta_keywords ?? '');
    $currentStatus = old('status', $product->status ?? \App\Models\Admin\Product\Product::STATUS_APPOINTMENT_PENDING);
    $currentFeatured = (bool) old('is_featured', (bool) ($product->is_featured ?? false));
    $currentActive = (bool) old('is_active', $product->is_active ?? true);
    $currentAppointmentId = old('appointment_id', $product->appointment_id ?? '');
    $selectedCategoryIds = old('category_ids', $selectedCategoryIds ?? []);
    $featuredMediaId = old('featured_media_id', $featuredMediaId ?? null);
    $currentFeaturedUrl = $product?->featuredMediaUrl() ?? $product?->featured_image_url;
    $statusOptions = $statusOptions ?? \App\Models\Admin\Product\Product::statusOptionsSorted();
    $currentStatusMeta = $statusOptions[$currentStatus] ?? ($statusOptions[\App\Models\Admin\Product\Product::STATUS_APPOINTMENT_PENDING] ?? null);
    $initialWordCount = $isEdit ? $product->contentWordCount() : 0;
    $initialReadTime = $isEdit ? $product->estimatedReadTimeMinutes() : 0;
    $initialSeoScore = $isEdit ? $product->seoCompletenessScore() : 0;
    $initialPreviewTitle = $currentMetaTitle ?: ($currentTitle ?: 'Meta baslik burada gorunecek');
    $initialPreviewDescription = $currentMetaDescription ?: ($product?->excerptPreview(155) ?: 'Meta aciklama burada gorunecek.');
    $resolvedPrice = $currentPrice !== '' ? number_format((float) $currentPrice, 2, ',', '.') . ' ' . ($currentCurrency ?: 'TRY') : 'Fiyat bilgisi yok';
    $stockBadgeClass = ((int) $currentStock) <= 0 ? 'kt-badge-light-danger' : (((int) $currentStock) <= 5 ? 'kt-badge-light-warning' : 'kt-badge-light-success');
    $stockBadgeLabel = $currentStock === '' ? 'Stok bekleniyor' : (((int) $currentStock) <= 0 ? 'Stok yok' : (((int) $currentStock) <= 5 ? 'Dusuk stok: ' . (int) $currentStock : 'Stok iyi: ' . (int) $currentStock));
@endphp

<div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.55fr)_400px] gap-6">
    <div class="grid gap-6">
        <div class="kt-card overflow-hidden">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Urun Icerigi</h3>
                    <div class="text-sm text-muted-foreground">
                        Baslik, slug ve ana icerigi tek akista yonetin.
                    </div>
                </div>
            </div>

            <div class="kt-card-content p-6 grid gap-6">
                <div class="grid gap-2">
                    <div class="flex items-center justify-between gap-3">
                        <label class="kt-form-label font-normal text-mono" for="title">Baslik</label>
                        <span class="text-xs text-muted-foreground" data-product-title-count>{{ mb_strlen($currentTitle) }}/255</span>
                    </div>
                    <input
                        id="title"
                        name="title"
                        class="kt-input @error('title') kt-input-invalid @enderror"
                        value="{{ $currentTitle }}"
                        placeholder="Urun basligini yazin"
                    >
                    @error('title')
                        <div class="text-xs text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="grid gap-3 rounded-3xl app-surface-card app-surface-card--soft p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <label class="kt-form-label font-normal text-mono mb-0" for="slug">Slug ve URL</label>
                        <span class="text-xs text-muted-foreground">URL stabilitesini korumak icin sadece gerektiginde degistirin.</span>
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
                        <span class="font-medium text-foreground">{{ url('/products') }}/<span id="url_slug_preview">{{ $currentSlug }}</span></span>
                    </div>

                    <div id="slugCheckHint" class="text-xs text-muted-foreground">
                        Slug girildiginde uygunluk kontrolu yapilir.
                    </div>
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between gap-3">
                        <label class="kt-form-label font-normal text-mono" for="content_editor">Urun Detayi</label>
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
                    <h3 class="kt-card-title">Ticari Bilgiler</h3>
                    <div class="text-sm text-muted-foreground">
                        Fiyat, stok, barkod ve lojistik alanlarini ayni blokta yonetin.
                    </div>
                </div>
            </div>

            <div class="kt-card-content p-6 grid gap-5">
                <div class="grid gap-5 md:grid-cols-3">
                    <div class="grid gap-2">
                        <label class="kt-form-label font-normal text-mono" for="sku">SKU</label>
                        <input id="sku" name="sku" class="kt-input @error('sku') kt-input-invalid @enderror" value="{{ $currentSku }}" placeholder="ABC-123">
                        @error('sku')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="kt-form-label font-normal text-mono" for="barcode">Barkod</label>
                        <input id="barcode" name="barcode" class="kt-input @error('barcode') kt-input-invalid @enderror" value="{{ $currentBarcode }}" placeholder="8690000000000">
                        @error('barcode')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="kt-form-label font-normal text-mono" for="brand">Marka</label>
                        <input id="brand" name="brand" class="kt-input @error('brand') kt-input-invalid @enderror" value="{{ $currentBrand }}" placeholder="Marka adi">
                        @error('brand')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-5 md:grid-cols-4">
                    <div class="grid gap-2">
                        <label class="kt-form-label font-normal text-mono" for="price">Fiyat</label>
                        <input id="price" name="price" type="number" step="0.01" min="0" class="kt-input @error('price') kt-input-invalid @enderror" value="{{ $currentPrice }}" placeholder="0.00">
                        @error('price')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="kt-form-label font-normal text-mono" for="sale_price">Indirimli Fiyat</label>
                        <input id="sale_price" name="sale_price" type="number" step="0.01" min="0" class="kt-input @error('sale_price') kt-input-invalid @enderror" value="{{ $currentSalePrice }}" placeholder="0.00">
                        @error('sale_price')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="kt-form-label font-normal text-mono" for="currency">Para Birimi</label>
                        <input id="currency" name="currency" maxlength="3" class="kt-input @error('currency') kt-input-invalid @enderror" value="{{ $currentCurrency }}" placeholder="TRY">
                        @error('currency')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="kt-form-label font-normal text-mono" for="vat_rate">KDV (%)</label>
                        <input id="vat_rate" name="vat_rate" type="number" min="0" max="100" class="kt-input @error('vat_rate') kt-input-invalid @enderror" value="{{ $currentVatRate }}" placeholder="20">
                        @error('vat_rate')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-5 md:grid-cols-5">
                    <div class="grid gap-2">
                        <label class="kt-form-label font-normal text-mono" for="stock">Stok</label>
                        <input id="stock" name="stock" type="number" min="0" class="kt-input @error('stock') kt-input-invalid @enderror" value="{{ $currentStock }}" placeholder="0">
                        @error('stock')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="kt-form-label font-normal text-mono" for="weight">Agirlik (kg)</label>
                        <input id="weight" name="weight" type="number" step="0.001" min="0" class="kt-input @error('weight') kt-input-invalid @enderror" value="{{ $currentWeight }}" placeholder="0.000">
                        @error('weight')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="kt-form-label font-normal text-mono" for="width">En (cm)</label>
                        <input id="width" name="width" type="number" step="0.001" min="0" class="kt-input @error('width') kt-input-invalid @enderror" value="{{ $currentWidth }}" placeholder="0.000">
                        @error('width')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="kt-form-label font-normal text-mono" for="height">Boy (cm)</label>
                        <input id="height" name="height" type="number" step="0.001" min="0" class="kt-input @error('height') kt-input-invalid @enderror" value="{{ $currentHeight }}" placeholder="0.000">
                        @error('height')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="kt-form-label font-normal text-mono" for="length">Derinlik (cm)</label>
                        <input id="length" name="length" type="number" step="0.001" min="0" class="kt-input @error('length') kt-input-invalid @enderror" value="{{ $currentLength }}" placeholder="0.000">
                        @error('length')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div class="grid gap-2">
                        <label class="kt-form-label font-normal text-mono" for="sort_order">Liste Sirasi</label>
                        <input id="sort_order" name="sort_order" type="number" min="0" class="kt-input @error('sort_order') kt-input-invalid @enderror" value="{{ $currentSortOrder }}">
                        @error('sort_order')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="kt-form-label font-normal text-mono" for="appointment_id">Randevu ID</label>
                        <input id="appointment_id" name="appointment_id" type="number" min="1" class="kt-input @error('appointment_id') kt-input-invalid @enderror" value="{{ $currentAppointmentId }}" placeholder="Opsiyonel">
                        @error('appointment_id')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="kt-card overflow-hidden">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">SEO ve Arama Onizlemesi</h3>
                    <div class="text-sm text-muted-foreground">
                        Meta alanlarini girerken arama sonucunda gorunumu canli izleyin.
                    </div>
                </div>
            </div>

            <div class="kt-card-content p-6 grid gap-6 lg:grid-cols-[minmax(0,1.15fr)_320px]">
                <div class="grid gap-5">
                    <div class="grid gap-2">
                        <div class="flex items-center justify-between gap-3">
                            <label class="kt-form-label font-normal text-mono">Meta Title</label>
                            <span class="text-xs text-muted-foreground" data-product-meta-title-count>{{ mb_strlen($currentMetaTitle) }}/60 onerisi</span>
                        </div>
                        <input
                            name="meta_title"
                            class="kt-input @error('meta_title') kt-input-invalid @enderror"
                            value="{{ $currentMetaTitle }}"
                            placeholder="Arama sonucunda gorunecek baslik"
                        >
                        @error('meta_title')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <div class="flex items-center justify-between gap-3">
                            <label class="kt-form-label font-normal text-mono">Meta Description</label>
                            <span class="text-xs text-muted-foreground" data-product-meta-description-count>{{ mb_strlen($currentMetaDescription) }}/160 onerisi</span>
                        </div>
                        <textarea
                            name="meta_description"
                            rows="4"
                            class="kt-textarea @error('meta_description') kt-input-invalid @enderror"
                            placeholder="Arama sonucunda gorunecek aciklama"
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
                            <div class="text-base font-semibold leading-6 text-primary" data-product-seo-preview-title>
                                {{ $initialPreviewTitle }}
                            </div>
                            <div class="text-sm text-success">
                                {{ url('/products') }}/<span data-product-seo-preview-slug>{{ $currentSlug ?: 'ornek-urun' }}</span>
                            </div>
                            <div class="text-sm leading-6 text-muted-foreground" data-product-seo-preview-description>
                                {{ $initialPreviewDescription }}
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl app-surface-card app-surface-card--soft p-4 text-sm text-muted-foreground">
                        Meta title icin 30-60, meta description icin 100-160 karakter araligi daha dengeli gorunur.
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
                    <div class="text-sm text-muted-foreground">
                        Workflow, aktiflik ve anasayfa vitrini ayarlarini yonetin.
                    </div>
                </div>
            </div>

            <div class="kt-card-content p-6 grid gap-4">
                <div class="rounded-3xl app-surface-card app-surface-card--soft p-4">
                    <div class="grid gap-3">
                        <div class="flex items-center justify-between gap-3">
                            <div class="font-medium text-foreground">Workflow Durumu</div>
                            <span
                                id="product_status_badge"
                                class="{{ $currentStatusMeta['badge'] ?? 'kt-badge kt-badge-sm kt-badge-light' }}"
                            >
                                {{ $currentStatusMeta['label'] ?? $currentStatus }}
                            </span>
                        </div>

                        <select
                            id="product_status"
                            name="status"
                            class="kt-select w-full @error('status') kt-input-invalid @enderror"
                            data-kt-select="true"
                            data-kt-select-placeholder="Durum"
                        >
                            @foreach($statusOptions as $key => $option)
                                <option value="{{ $key }}" @selected($currentStatus === $key)>{{ $option['label'] }}</option>
                            @endforeach
                        </select>

                        <div class="text-xs text-muted-foreground" data-product-status-hint>
                            Workflow secimi yapildiginda urunun operasyonel asamasi netlesir.
                        </div>

                        @error('status')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="rounded-3xl app-surface-card app-surface-card--soft p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="grid gap-1">
                            <div class="font-medium text-foreground">Anasayfa Vitrini</div>
                            <div class="text-sm text-muted-foreground">En fazla 5 urun one cikarilabilir.</div>
                        </div>

                        <div class="flex items-center gap-3">
                            <input type="hidden" name="is_featured" value="0">
                            <label class="kt-switch kt-switch-sm">
                                <input
                                    type="checkbox"
                                    class="kt-switch"
                                    id="product_is_featured"
                                    name="is_featured"
                                    value="1"
                                    @checked($currentFeatured)
                                >
                            </label>
                            <span
                                id="product_featured_badge"
                                class="kt-badge kt-badge-sm {{ $currentFeatured ? 'kt-badge-light-success' : 'kt-badge-light text-muted-foreground' }}"
                            >
                                {{ $currentFeatured ? 'Anasayfada' : 'Kapali' }}
                            </span>
                        </div>
                    </div>

                    @error('is_featured')
                        <div class="mt-2 text-xs text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="rounded-3xl app-surface-card app-surface-card--soft p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="grid gap-1">
                            <div class="font-medium text-foreground">Aktiflik</div>
                            <div class="text-sm text-muted-foreground">Listeleme ve operasyonel kullanim kontrolu.</div>
                        </div>

                        <div class="flex items-center gap-3">
                            <input type="hidden" name="is_active" value="0">
                            <label class="kt-switch kt-switch-sm">
                                <input
                                    type="checkbox"
                                    class="kt-switch"
                                    id="product_is_active"
                                    name="is_active"
                                    value="1"
                                    @checked($currentActive)
                                >
                            </label>
                            <span
                                id="product_active_badge"
                                class="kt-badge kt-badge-sm {{ $currentActive ? 'kt-badge-light-success' : 'kt-badge-light text-muted-foreground' }}"
                            >
                                {{ $currentActive ? 'Aktif' : 'Pasif' }}
                            </span>
                        </div>
                    </div>

                    @error('is_active')
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
                        Icerik, fiyat, stok ve SEO kalitesini anlik izleyin.
                    </div>
                </div>
            </div>

            <div class="kt-card-content p-6 grid gap-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-3xl app-surface-card p-4">
                        <div class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Kelime</div>
                        <div class="mt-2 text-2xl font-semibold text-foreground" data-product-word-count>{{ $initialWordCount }} kelime</div>
                    </div>
                    <div class="rounded-3xl app-surface-card p-4">
                        <div class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Okuma</div>
                        <div class="mt-2 text-2xl font-semibold text-foreground" data-product-read-time>{{ $initialReadTime }} dk</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-3xl app-surface-card p-4">
                        <div class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Fiyat</div>
                        <div class="mt-2 text-lg font-semibold text-foreground" data-product-price-preview>{{ $resolvedPrice }}</div>
                    </div>
                    <div class="rounded-3xl app-surface-card p-4">
                        <div class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Stok</div>
                        <div class="mt-2">
                            <span class="kt-badge kt-badge-sm {{ $stockBadgeClass }}" data-product-stock-badge>{{ $stockBadgeLabel }}</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl app-surface-card app-surface-card--soft p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-xs uppercase tracking-[0.18em] text-muted-foreground">SEO Tamamlilik</div>
                            <div class="mt-1 text-sm text-muted-foreground" data-product-seo-summary>
                                {{ $initialSeoScore >= 80 ? 'SEO hazirligi guclu gorunuyor.' : ($initialSeoScore >= 50 ? 'Temel alanlar iyi, birkac iyilestirme daha yapilabilir.' : 'Meta alanlari ve one cikan gorsel tarafini guclendirmek faydali olur.') }}
                            </div>
                        </div>
                        <div
                            class="text-3xl font-semibold {{ $initialSeoScore >= 80 ? 'text-success' : ($initialSeoScore >= 50 ? 'text-warning' : 'text-danger') }}"
                            data-product-seo-score
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
                        Urunu dogru kategorilerle etiketleyerek bulunurlugu artirin.
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
                    Birden fazla kategori secilebilir. Alt kategoriler hiyerarsi korunarak listelenir.
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
            'fileName' => 'featured_image',
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
                        <h3 class="kt-card-title">Kayit Bilgisi</h3>
                        <div class="text-sm text-muted-foreground">
                            Bu urunun son guncelleme ozetini izleyin.
                        </div>
                    </div>
                </div>

                <div class="kt-card-content p-6 grid gap-3 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-muted-foreground">Kayit ID</span>
                        <span class="font-medium text-foreground">#{{ $product->id }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-muted-foreground">Olusturulma</span>
                        <span class="font-medium text-foreground">{{ $product->created_at?->format('d.m.Y H:i') }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-muted-foreground">Son guncelleme</span>
                        <span class="font-medium text-foreground">{{ $product->updated_at?->format('d.m.Y H:i') }}</span>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
