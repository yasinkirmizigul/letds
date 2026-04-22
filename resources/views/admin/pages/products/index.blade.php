@extends('admin.layouts.main.app')

@section('content')
    @php
        $mode = $mode ?? 'active';
        $isTrash = $mode === 'trash';
        $q = $q ?? '';
        $status = $status ?? 'all';
        $selectedCategoryIds = $selectedCategoryIds ?? [];
    @endphp

    <div
        class="kt-container-fixed max-w-[96%] grid gap-5 lg:gap-7.5"
        data-page="products.index"
        data-mode="{{ $mode }}"
        data-perpage="{{ $perPage ?? 25 }}"
        data-status-options='@json($statusOptions ?? [])'
        data-bulk-delete-url="{{ route('admin.products.bulkDestroy') }}"
        data-bulk-restore-url="{{ route('admin.products.bulkRestore') }}"
        data-bulk-force-delete-url="{{ route('admin.products.bulkForceDestroy') }}"
    >
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-xl font-semibold">
                    {{ $isTrash ? 'Ürünler Çöp Kutusu' : 'Ürün Yönetimi' }}
                </h1>
                <div class="text-sm text-muted-foreground">
                    {{ $isTrash ? 'Silinen ürünleri geri yükleyebilir veya kalıcı olarak silebilirsiniz.' : 'Workflow, fiyat, stok ve vitrin akışlarını tek ekrandan yönetin.' }}
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.products.index') }}" class="kt-btn kt-btn-sm {{ $isTrash ? 'kt-btn-light' : 'kt-btn-primary' }}">
                    Aktif Kayıtlar
                </a>
                <a href="{{ route('admin.products.trash') }}" class="kt-btn kt-btn-sm {{ $isTrash ? 'kt-btn-primary' : 'kt-btn-light' }}">
                    Çöp Kutusu
                </a>

                @perm('products.create')
                    <a href="{{ route('admin.products.create') }}" class="kt-btn kt-btn-sm kt-btn-primary">
                        Yeni Ürün
                    </a>
                @endperm
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Toplam</div>
                <div class="mt-2 text-3xl font-semibold text-foreground">{{ $stats['all'] ?? 0 }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Aktif</div>
                <div class="mt-2 text-3xl font-semibold text-success">{{ $stats['active'] ?? 0 }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Anasayfada</div>
                <div class="mt-2 text-3xl font-semibold text-primary">{{ $stats['featured'] ?? 0 }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Düşük Stok</div>
                <div class="mt-2 text-3xl font-semibold text-warning">{{ $stats['low_stock'] ?? 0 }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Çöpte</div>
                <div class="mt-2 text-3xl font-semibold text-danger">{{ $stats['trash'] ?? 0 }}</div>
            </div>
        </div>

        <div class="kt-card kt-card-grid min-w-full">
            <div class="kt-card-header py-5 flex-wrap gap-4">
                <div>
                    <h3 class="kt-card-title">{{ $isTrash ? 'Silinen Ürünler' : 'Ürün Listesi' }}</h3>
                    <div class="text-sm text-muted-foreground">
                        Durum, stok, kategori ve vitrin seçimini tek satırda inceleyin.
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <input
                        id="productsSearch"
                        type="text"
                        class="kt-input kt-input-sm w-full md:w-[260px]"
                        placeholder="Başlık, slug, SKU, barkod ara..."
                        value="{{ $q }}"
                    />

                    <select
                        id="productsStatusFilter"
                        class="kt-select w-full md:w-[220px]"
                        data-kt-select="true"
                        data-kt-select-placeholder="Durum"
                    >
                        <option value="all" @selected($status === 'all')>Tüm durumlar</option>
                        @foreach(($statusOptions ?? []) as $key => $option)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $option['label'] }}</option>
                        @endforeach
                    </select>

                    <select
                        id="productsCategoryFilter"
                        class="kt-select w-full md:w-[250px]"
                        multiple
                        data-kt-select="true"
                        data-kt-select-placeholder="Kategoriler"
                        data-kt-select-multiple="true"
                        data-kt-select-tags="false"
                        data-kt-select-config='{"showSelectedCount":true,"enableSelectAll":true,"selectAllText":"Tümünü Seç","clearAllText":"Temizle"}'
                    >
                        @foreach(($categoryOptions ?? []) as $option)
                            <option value="{{ $option['id'] }}" @selected(in_array($option['id'], $selectedCategoryIds))>
                                {{ $option['label'] }}
                            </option>
                        @endforeach
                    </select>

                    <button type="button" id="productsClearFiltersBtn" class="kt-btn kt-btn-sm kt-btn-light">
                        Filtreleri Temizle
                    </button>
                </div>
            </div>

            <div class="kt-card-content">
                <div id="productsBulkBar" class="hidden kt-card mb-4 border border-border">
                    <div class="kt-card-content p-3 flex items-center justify-between gap-3">
                        <div class="text-sm text-muted-foreground">
                            Seçili: <b id="productsSelectedCount">0</b>
                        </div>

                        <div class="flex items-center gap-2">
                            @if($isTrash)
                                @perm('products.restore')
                                    <button type="button" class="kt-btn kt-btn-sm kt-btn-success" id="productsBulkRestoreBtn" disabled>
                                        Geri Yükle
                                    </button>
                                @endperm
                                @perm('products.force_delete')
                                    <button type="button" class="kt-btn kt-btn-sm kt-btn-destructive" id="productsBulkForceDeleteBtn" disabled>
                                        Kalıcı Sil
                                    </button>
                                @endperm
                            @else
                                @perm('products.delete')
                                    <button type="button" class="kt-btn kt-btn-sm kt-btn-destructive" id="productsBulkDeleteBtn" disabled>
                                        Sil
                                    </button>
                                @endperm
                            @endif
                        </div>
                    </div>
                </div>

                <div class="grid" id="products_dt">
                    <div class="kt-scrollable-x-auto overflow-y-hidden">
                        <table id="products_table" class="kt-table table-auto kt-table-border w-full">
                            <thead>
                            <tr>
                                <th class="w-[55px] dt-orderable-none">
                                    <input class="kt-checkbox kt-checkbox-sm" id="products_check_all" type="checkbox">
                                </th>
                                <th class="min-w-[360px]">Ürün</th>
                                <th class="min-w-[220px]">Ticari Durum</th>
                                <th class="min-w-[220px]">Workflow</th>
                                <th class="min-w-[220px]">Vitrin</th>
                                <th class="min-w-[180px]">Son Güncelleme</th>
                                <th class="w-[64px]"></th>
                                <th class="w-[80px]"></th>
                            </tr>
                            </thead>

                            <tbody>
                            @foreach($products as $product)
                                @php
                                    $img = $product->featuredMediaUrl() ?: $product->featured_image_url;
                                    $seoScore = $product->seoCompletenessScore();
                                    $readTime = $product->estimatedReadTimeMinutes();
                                    $categoryIdsAttr = '|' . $product->categories->pluck('id')->map(fn ($id) => (int) $id)->implode('|') . '|';
                                    $stock = is_null($product->stock) ? null : (int) $product->stock;
                                @endphp

                                <tr
                                    data-id="{{ $product->id }}"
                                    data-status="{{ $product->status }}"
                                    data-featured="{{ $product->is_featured ? '1' : '0' }}"
                                    data-category-ids="{{ $categoryIdsAttr }}"
                                >
                                    <td class="w-[55px]">
                                        <input class="kt-checkbox kt-checkbox-sm products-check" type="checkbox" value="{{ $product->id }}">
                                    </td>

                                    <td>
                                        <div class="flex items-start gap-3">
                                            <div class="w-12 h-12 rounded-2xl overflow-hidden border border-border bg-muted/20 shrink-0">
                                                @if($img)
                                                    <a
                                                        href="javascript:void(0)"
                                                        class="block w-full h-full js-img-popover"
                                                        data-popover-img="{{ $img }}"
                                                    >
                                                        <img src="{{ $img }}" class="w-full h-full object-cover" alt="">
                                                    </a>
                                                @else
                                                    <div class="w-full h-full grid place-items-center text-muted-foreground">
                                                        <i class="ki-outline ki-picture text-xl"></i>
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="grid gap-2 min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    @if(!$isTrash)
                                                        <a class="font-semibold text-foreground hover:text-primary" href="{{ route('admin.products.edit', $product->id) }}">
                                                            {{ $product->title }}
                                                        </a>
                                                    @else
                                                        <span class="font-semibold text-foreground">{{ $product->title }}</span>
                                                    @endif
                                                    <span class="kt-badge kt-badge-sm kt-badge-light">#{{ $product->id }}</span>
                                                    <span class="kt-badge kt-badge-sm {{ $seoScore >= 80 ? 'kt-badge-light-success' : ($seoScore >= 50 ? 'kt-badge-light-warning' : 'kt-badge-light-danger') }}">
                                                        SEO %{{ $seoScore }}
                                                    </span>
                                                    <span class="kt-badge kt-badge-sm kt-badge-light">
                                                        {{ $readTime > 0 ? $readTime . ' dk okuma' : 'Kısa içerik' }}
                                                    </span>
                                                </div>

                                                <div class="text-sm text-muted-foreground break-all">
                                                    /products/{{ $product->slug }}
                                                </div>

                                                <div class="text-sm text-muted-foreground leading-6">
                                                    {{ $product->excerptPreview(130) ?: 'İçerik özeti bulunmuyor.' }}
                                                </div>

                                                <div class="flex flex-wrap items-center gap-1">
                                                    @if($product->sku)
                                                        <span class="kt-badge kt-badge-sm kt-badge-light">SKU: {{ $product->sku }}</span>
                                                    @endif
                                                    @if($product->brand)
                                                        <span class="kt-badge kt-badge-sm kt-badge-light">{{ $product->brand }}</span>
                                                    @endif
                                                    @foreach($product->categories as $category)
                                                        <span class="kt-badge kt-badge-sm kt-badge-light">{{ $category->name }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="grid gap-2">
                                            <div class="text-sm font-medium text-foreground">
                                                @if(!is_null($product->price))
                                                    {{ number_format((float) $product->price, 2, ',', '.') }} {{ $product->currency ?: 'TRY' }}
                                                @else
                                                    Fiyat yok
                                                @endif
                                            </div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                @if(!is_null($product->sale_price))
                                                    <span class="kt-badge kt-badge-sm kt-badge-light-success">Indirim: {{ number_format((float) $product->sale_price, 2, ',', '.') }}</span>
                                                @endif
                                                <span class="kt-badge kt-badge-sm {{ is_null($stock) ? 'kt-badge-light' : ($stock <= 0 ? 'kt-badge-light-danger' : ($stock <= 5 ? 'kt-badge-light-warning' : 'kt-badge-light-success')) }}">
                                                    {{ is_null($stock) ? 'Stok belirtilmedi' : ($stock <= 0 ? 'Stok yok' : ($stock <= 5 ? 'Düşük stok: ' . $stock : 'Stok: ' . $stock)) }}
                                                </span>
                                            </div>
                                            <div class="text-xs text-muted-foreground">
                                                {{ $product->is_active ? 'Operasyonel olarak aktif' : 'Pasif durumda' }}
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <button
                                            type="button"
                                            class="{{ \App\Models\Admin\Product\Product::statusBadgeClass($product->status) }} js-status-trigger"
                                            data-status="{{ $product->status }}"
                                            data-status-url="{{ route('admin.products.status', $product) }}"
                                            @disabled($isTrash)
                                        >
                                            {{ \App\Models\Admin\Product\Product::statusLabel($product->status) }}
                                            <i class="ki-outline ki-down ml-1"></i>
                                        </button>
                                    </td>

                                    <td>
                                        <div class="grid gap-2">
                                            <label class="inline-flex items-center gap-3">
                                                <input
                                                    type="checkbox"
                                                    class="kt-switch kt-switch-sm js-featured-toggle"
                                                    data-url="{{ route('admin.products.featured', $product) }}"
                                                    @checked($product->is_featured)
                                                    @disabled($isTrash)
                                                >
                                                <span class="kt-badge kt-badge-sm {{ $product->is_featured ? 'kt-badge-light-success' : 'kt-badge-light text-muted-foreground' }} js-featured-badge">
                                                    {{ $product->is_featured ? 'Anasayfada' : 'Kapalı' }}
                                                </span>
                                            </label>
                                            <div class="text-xs text-muted-foreground js-featured-at">
                                                {{ $product->is_featured && $product->featured_at ? 'Seçim: ' . $product->featured_at->format('d.m.Y H:i') : 'Seçim yapılmamış' }}
                                            </div>
                                        </div>
                                    </td>

                                    <td class="text-sm text-muted-foreground">
                                        <div class="grid gap-0.5">
                                            <span>{{ $product->updated_at?->format('d.m.Y') }}</span>
                                            <span class="text-xs">{{ $product->updated_at?->format('H:i') }}</span>
                                        </div>
                                    </td>

                                    <td class="text-right">
                                        @if(!$isTrash)
                                            @perm('products.update')
                                                <a class="kt-btn kt-btn-sm kt-btn-light" href="{{ route('admin.products.edit', $product) }}">
                                                    Düzenle
                                                </a>
                                            @endperm
                                        @endif
                                    </td>

                                    <td class="text-right">
                                        <div class="inline-flex items-center gap-1">
                                            @if($isTrash)
                                                @perm('products.restore')
                                                    <button type="button" class="kt-btn kt-btn-sm kt-btn-light" data-action="restore" data-url="{{ route('admin.products.restore', $product->id) }}">
                                                        Geri Al
                                                    </button>
                                                @endperm
                                                @perm('products.force_delete')
                                                    <button type="button" class="kt-btn kt-btn-sm kt-btn-danger" data-action="force-delete" data-url="{{ route('admin.products.forceDestroy', $product->id) }}">
                                                        Kalıcı Sil
                                                    </button>
                                                @endperm
                                            @else
                                                @perm('products.delete')
                                                    <button type="button" class="kt-btn kt-btn-sm kt-btn-danger" data-action="delete" data-url="{{ route('admin.products.destroy', $product) }}">
                                                        Sil
                                                    </button>
                                                @endperm
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <template id="dt-empty-products">
                        <tr data-kt-empty-row="true">
                            <td colspan="8" class="py-12">
                                <div class="grid place-items-center gap-2 text-center text-muted-foreground">
                                    <i class="ki-outline ki-box text-4xl"></i>
                                    <div class="font-semibold">{{ $isTrash ? 'Silinen ürün yok' : 'Henüz ürün yok' }}</div>
                                    <div class="text-sm">{{ $isTrash ? 'Geri yüklenebilir ürün bulunmuyor.' : 'Yeni ürün ekleyerek bu listeyi doldurabilirsiniz.' }}</div>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template id="dt-zero-products">
                        <tr data-kt-zero-row="true">
                            <td colspan="8" class="py-12">
                                <div class="grid place-items-center gap-2 text-center text-muted-foreground">
                                    <i class="ki-outline ki-search-list text-4xl"></i>
                                    <div class="font-semibold">Sonuç bulunamadı</div>
                                    <div class="text-sm">Arama veya filtreleri değiştirip tekrar deneyin.</div>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <div class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
                        <div class="flex items-center gap-2 order-2 md:order-1">
                            Göster
                            <select class="kt-select w-16" id="productsPageSize" data-kt-select="true"></select>
                            / sayfa
                        </div>

                        <div class="flex items-center gap-4 order-1 md:order-2">
                            <span id="productsInfo"></span>
                            <div class="kt-datatable-pagination" id="productsPagination"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
