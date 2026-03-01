@php
    $product = $product ?? null;

    $categories = $categories ?? collect();
    $selectedCategoryIds = $selectedCategoryIds ?? [];
    $featuredMediaId = $featuredMediaId ?? null;

    $st = old('status', $product->status ?? 'draft');

    $slugVal = old('slug', $product->slug ?? '');
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- LEFT --}}
    <div class="lg:col-span-2 flex flex-col gap-6">

        <div>
            <label class="kt-form-label mb-3">Başlık</label>
            <input class="kt-input"
                   name="title"
                   id="title"
                   value="{{ old('title', $product->title ?? '') }}"/>
            @error('title')
            <div class="text-danger text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="kt-form-label mb-3">Slug</label>

                <div class="flex items-center gap-2">
                    <input class="kt-input"
                           name="slug"
                           id="slug"
                           value="{{ $slugVal }}"/>

                    <label class="kt-switch shrink-0" title="Otomatik slug">
                        <input type="checkbox" class="kt-switch" id="slug_auto" checked>
                        <span class="kt-switch-slider"></span>
                    </label>

                    <button type="button" class="kt-btn kt-btn-light shrink-0" id="slug_regen">
                        Oluştur
                    </button>
                </div>

                <div class="text-xs text-muted-foreground mt-2">
                    URL Önizleme:
                    <span class="font-medium">
                        {{ url('/products') }}/<span id="url_slug_preview">{{ $slugVal }}</span>
                    </span>
                </div>

                @error('slug')
                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="kt-form-label mb-3">Status</label>
                <select class="kt-select" name="status" id="productStatus" data-kt-select="true">
                    <option value="draft" @selected($st === 'draft')>draft</option>
                    <option value="active" @selected($st === 'active')>active</option>
                    <option value="archived" @selected($st === 'archived')>archived</option>
                    <option value="appointment_pending" @selected($st === 'appointment_pending')>appointment_pending</option>
                </select>
                @error('status')
                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                @enderror
            </div>
        </div>

        {{-- ✅ Optional product fields --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="kt-form-label mb-3">SKU (opsiyonel)</label>
                <input class="kt-input"
                       name="sku"
                       value="{{ old('sku', $product->sku ?? '') }}"
                       placeholder="Örn: ABC-123" />
                @error('sku')
                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="kt-form-label mb-3">Fiyat (opsiyonel)</label>
                <input class="kt-input"
                       type="number"
                       step="0.01"
                       min="0"
                       name="price"
                       value="{{ old('price', $product->price ?? '') }}"
                       placeholder="0.00" />
                @error('price')
                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="kt-form-label mb-3">Stok (opsiyonel)</label>
                <input class="kt-input"
                       type="number"
                       step="1"
                       min="0"
                       name="stock"
                       value="{{ old('stock', $product->stock ?? '') }}"
                       placeholder="0" />
                @error('stock')
                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="flex flex-col gap-2">
            <label class="kt-form-label font-normal text-mono">İçerik</label>
            <textarea id="content_editor"
                      name="content"
                      class="kt-input min-h-[320px]">{{ old('content', $product->content ?? '') }}</textarea>
            @error('content')
            <div class="text-xs text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="kt-card kt-card-border">
            <div class="kt-card-header">
                <h3 class="kt-card-title">SEO</h3>
            </div>
            <div class="kt-card-content p-6 flex flex-col gap-4">
                <div>
                    <label class="kt-form-label mb-3">Meta Title</label>
                    <input class="kt-input"
                           name="meta_title"
                           value="{{ old('meta_title', $product->meta_title ?? '') }}"/>
                    @error('meta_title')
                    <div class="text-danger text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="kt-form-label mb-3">Meta Description</label>
                    <textarea class="kt-textarea"
                              name="meta_description">{{ old('meta_description', $product->meta_description ?? '') }}</textarea>
                    @error('meta_description')
                    <div class="text-danger text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="kt-form-label mb-3">Meta Keywords</label>
                    <input class="kt-input"
                           name="meta_keywords"
                           value="{{ old('meta_keywords', $product->meta_keywords ?? '') }}"/>
                    @error('meta_keywords')
                    <div class="text-danger text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

    </div>

    {{-- RIGHT --}}
    <div class="lg:col-span-1 flex flex-col gap-6">

        <div class="kt-card kt-card-border">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Kategoriler</h3>
            </div>

            <div class="kt-card-content p-6 flex flex-col gap-2">
                <select name="category_ids[]" multiple
                        class="kt-select @error('category_ids') kt-input-invalid @enderror"
                        data-kt-select="true"
                        data-kt-select-placeholder="Kategoriler"
                        data-kt-select-multiple="true"
                        data-kt-select-tags="true"
                        data-kt-select-config='{
                            "showSelectedCount": true,
                            "enableSelectAll": true,
                            "selectAllText": "Tümünü Seç",
                            "clearAllText": "Tümünü Temizle"
                        }'>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}"
                            @selected(in_array((int)$c->id, old('category_ids', $selectedCategoryIds)))>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
                @error('category_ids')
                <div class="text-xs text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>

        {{-- Gallery panel (create'te product yok; sadece edit'te göster) --}}
        @if($product?->id)
            @includeIf('admin.pages.products.partials._gallery', ['product' => $product])
        @endif

        @include('admin.components.featured-image-manager', [
            'fileName' => 'featured_image',
            'mediaIdName' => 'featured_media_id',
            'currentUrl' => ($product?->featuredMediaUrl()) ?? ($product?->featured_image_url),
            'currentMediaId' => $featuredMediaId,
            'title' => 'Öne Çıkan Görsel',
        ])

    </div>
</div>



    {{-- ✅ Ürün detayları (opsiyonel) --}}
    <div class="grid gap-4 md:grid-cols-3">
        <div class="grid gap-2">
            <label class="kt-label" for="sku">SKU</label>
            <input id="sku" name="sku" type="text" class="kt-input" value="{{ old('sku', $product->sku ?? '') }}">
        </div>

        <div class="grid gap-2">
            <label class="kt-label" for="barcode">Barkod</label>
            <input id="barcode" name="barcode" type="text" class="kt-input" value="{{ old('barcode', $product->barcode ?? '') }}">
        </div>

        <div class="grid gap-2">
            <label class="kt-label" for="brand">Marka</label>
            <input id="brand" name="brand" type="text" class="kt-input" value="{{ old('brand', $product->brand ?? '') }}">
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
        <div class="grid gap-2">
            <label class="kt-label" for="price">Fiyat</label>
            <input id="price" name="price" type="number" step="0.01" min="0" class="kt-input" value="{{ old('price', $product->price ?? '') }}">
        </div>

        <div class="grid gap-2">
            <label class="kt-label" for="sale_price">İndirimli Fiyat</label>
            <input id="sale_price" name="sale_price" type="number" step="0.01" min="0" class="kt-input" value="{{ old('sale_price', $product->sale_price ?? '') }}">
        </div>

        <div class="grid gap-2">
            <label class="kt-label" for="currency">Para Birimi</label>
            <input id="currency" name="currency" type="text" maxlength="3" class="kt-input" placeholder="TRY" value="{{ old('currency', $product->currency ?? '') }}">
        </div>

        <div class="grid gap-2">
            <label class="kt-label" for="vat_rate">KDV (%)</label>
            <input id="vat_rate" name="vat_rate" type="number" min="0" max="100" class="kt-input" value="{{ old('vat_rate', $product->vat_rate ?? '') }}">
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-5">
        <div class="grid gap-2">
            <label class="kt-label" for="stock">Stok</label>
            <input id="stock" name="stock" type="number" min="0" class="kt-input" value="{{ old('stock', $product->stock ?? '') }}">
        </div>

        <div class="grid gap-2">
            <label class="kt-label" for="weight">Ağırlık (kg)</label>
            <input id="weight" name="weight" type="number" step="0.001" min="0" class="kt-input" value="{{ old('weight', $product->weight ?? '') }}">
        </div>

        <div class="grid gap-2">
            <label class="kt-label" for="width">En (cm)</label>
            <input id="width" name="width" type="number" step="0.001" min="0" class="kt-input" value="{{ old('width', $product->width ?? '') }}">
        </div>

        <div class="grid gap-2">
            <label class="kt-label" for="height">Boy (cm)</label>
            <input id="height" name="height" type="number" step="0.001" min="0" class="kt-input" value="{{ old('height', $product->height ?? '') }}">
        </div>

        <div class="grid gap-2">
            <label class="kt-label" for="length">Derinlik (cm)</label>
            <input id="length" name="length" type="number" step="0.001" min="0" class="kt-input" value="{{ old('length', $product->length ?? '') }}">
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="grid gap-2">
            <label class="kt-label" for="sort_order">Sıra</label>
            <input id="sort_order" name="sort_order" type="number" min="0" class="kt-input" value="{{ old('sort_order', $product->sort_order ?? 0) }}">
        </div>

        <div class="flex items-center gap-3 mt-7">
            <label class="kt-label mb-0" for="is_active">Aktif</label>
            <input id="is_active" name="is_active" type="checkbox" value="1"
                   class="kt-switch"
                   {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }}>
        </div>
    </div>
