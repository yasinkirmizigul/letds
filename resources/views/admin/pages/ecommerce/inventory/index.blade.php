@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[96%] grid gap-6">
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <span class="kt-badge kt-badge-sm kt-badge-light-primary w-fit">E-Ticaret Operasyonu</span>
                <div>
                    <h1 class="text-xl font-semibold text-foreground">Stok ve Varyant Yönetimi</h1>
                    <div class="text-sm text-muted-foreground">
                        Ürün varyantlarını, SKU seviyesindeki stokları ve stok hareket defterini yönetin.
                    </div>
                </div>
            </div>
            <a href="{{ route('admin.products.index') }}" class="kt-btn kt-btn-light">
                <i class="ki-filled ki-handcart"></i>
                Ürün Kataloğu
            </a>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Ürün</div>
                <div class="mt-2 text-3xl font-semibold">{{ $stats['products'] }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Varyant</div>
                <div class="mt-2 text-3xl font-semibold text-primary">{{ $stats['variants'] }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Düşük Ürün Stoku</div>
                <div class="mt-2 text-3xl font-semibold text-warning">{{ $stats['low_products'] }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Düşük Varyant Stoku</div>
                <div class="mt-2 text-3xl font-semibold text-danger">{{ $stats['low_variants'] }}</div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_420px]">
            <div class="kt-card">
                <div class="kt-card-header py-5 flex-wrap gap-4">
                    <div>
                        <h3 class="kt-card-title">Katalog Stokları</h3>
                        <div class="text-sm text-muted-foreground">Ürün bazında varyant ekleyin ve stok seviyelerini izleyin.</div>
                    </div>
                    <form method="GET" class="flex items-center gap-2">
                        <input name="q" value="{{ $search }}" class="kt-input w-[260px]" placeholder="Ürün, SKU veya marka ara">
                        <button class="kt-btn kt-btn-light" type="submit">Ara</button>
                    </form>
                </div>

                <div class="kt-card-content grid gap-4 p-6">
                    @forelse($products as $product)
                        <div class="rounded-[28px] app-surface-card p-5">
                            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                <div class="grid gap-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <div class="text-lg font-semibold text-foreground">{{ $product->title }}</div>
                                        <span class="kt-badge kt-badge-sm kt-badge-light">{{ $product->sku ?: 'SKU yok' }}</span>
                                        @if(!is_null($product->stock) && (int) $product->stock <= 5)
                                            <span class="kt-badge kt-badge-sm kt-badge-light-warning">Düşük stok</span>
                                        @endif
                                    </div>
                                    <div class="text-sm text-muted-foreground">
                                        Ana stok: {{ is_null($product->stock) ? 'Takip edilmiyor' : number_format((float) $product->stock, 0, ',', '.') }}
                                        · Fiyat: {{ number_format((float) ($product->sale_price ?? $product->price ?? 0), 2, ',', '.') }} {{ $product->currency ?: 'TRY' }}
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('admin.ecommerce.inventory.movements.store') }}" class="flex flex-wrap items-end gap-2">
                                    @csrf
                                    <input type="hidden" name="target_type" value="product">
                                    <input type="hidden" name="target_id" value="{{ $product->id }}">
                                    <select name="type" class="kt-select w-[140px]">
                                        @foreach($movementTypeOptions as $typeKey => $typeLabel)
                                            <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                                        @endforeach
                                    </select>
                                    <input name="quantity" type="number" step="0.001" min="0.001" class="kt-input w-[110px]" placeholder="Adet">
                                    <input name="reason" class="kt-input w-[160px]" placeholder="Neden">
                                    <button class="kt-btn kt-btn-primary" type="submit">Hareket</button>
                                </form>
                            </div>

                            <div class="mt-4 grid gap-3">
                                @foreach($product->variants as $variant)
                                    <div class="rounded-2xl border border-border bg-background/70 p-4">
                                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                            <div>
                                                <div class="font-semibold text-foreground">{{ $variant->title }}</div>
                                                <div class="mt-1 text-sm text-muted-foreground">
                                                    {{ $variant->displaySku() }} · Stok: {{ is_null($variant->stock) ? 'Takip edilmiyor' : number_format((float) $variant->stock, 3, ',', '.') }} · {{ $variant->money() }}
                                                </div>
                                            </div>
                                            <form method="POST" action="{{ route('admin.ecommerce.inventory.movements.store') }}" class="flex flex-wrap items-center gap-2">
                                                @csrf
                                                <input type="hidden" name="target_type" value="variant">
                                                <input type="hidden" name="target_id" value="{{ $variant->id }}">
                                                <select name="type" class="kt-select w-[130px]">
                                                    @foreach($movementTypeOptions as $typeKey => $typeLabel)
                                                        <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                                                    @endforeach
                                                </select>
                                                <input name="quantity" type="number" step="0.001" min="0.001" class="kt-input w-[110px]" placeholder="Adet">
                                                <button class="kt-btn kt-btn-sm kt-btn-light-primary" type="submit">İşle</button>
                                            </form>
                                        </div>
                                        <details class="mt-3 rounded-2xl border border-dashed border-border p-4">
                                            <summary class="cursor-pointer text-sm font-medium text-primary">Varyantı düzenle</summary>
                                            <form method="POST" action="{{ route('admin.ecommerce.inventory.variants.update', $variant) }}" class="mt-4 grid gap-3 lg:grid-cols-4">
                                                @csrf
                                                @method('PUT')
                                                <input name="title" class="kt-input" value="{{ $variant->title }}" placeholder="Varyant adı">
                                                <input name="sku" class="kt-input" value="{{ $variant->sku }}" placeholder="SKU">
                                                <input name="barcode" class="kt-input" value="{{ $variant->barcode }}" placeholder="Barkod">
                                                <input name="option_values_text" class="kt-input" value="{{ implode(', ', $variant->option_values ?? []) }}" placeholder="Seçenekler">
                                                <input name="stock" type="number" step="0.001" min="0" class="kt-input" value="{{ $variant->stock }}" placeholder="Stok">
                                                <input name="price" type="number" step="0.01" min="0" class="kt-input" value="{{ $variant->price }}" placeholder="Fiyat">
                                                <input name="sale_price" type="number" step="0.01" min="0" class="kt-input" value="{{ $variant->sale_price }}" placeholder="İndirimli fiyat">
                                                <input name="currency" class="kt-input uppercase" value="{{ $variant->currency ?: 'TRY' }}" maxlength="3" placeholder="TRY">
                                                <input name="low_stock_threshold" type="number" step="0.001" min="0" class="kt-input" value="{{ $variant->low_stock_threshold }}" placeholder="Düşük stok eşiği">
                                                <input name="sort_order" type="number" min="0" class="kt-input" value="{{ $variant->sort_order }}" placeholder="Sıra">
                                                <label class="flex items-center gap-2 text-sm text-muted-foreground">
                                                    <input type="hidden" name="is_active" value="0">
                                                    <input type="checkbox" name="is_active" value="1" class="kt-checkbox" @checked($variant->is_active)>
                                                    Aktif
                                                </label>
                                                <div class="flex flex-wrap items-center gap-2 lg:col-span-4">
                                                    <button type="submit" class="kt-btn kt-btn-primary">Değişiklikleri Kaydet</button>
                                                </div>
                                            </form>
                                            <form method="POST" action="{{ route('admin.ecommerce.inventory.variants.destroy', $variant) }}" class="mt-3" onsubmit="return confirm('Bu varyant silinsin mi?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="kt-btn kt-btn-sm kt-btn-danger">Varyantı Sil</button>
                                            </form>
                                        </details>
                                    </div>
                                @endforeach

                                <details class="rounded-2xl border border-dashed border-border bg-background/70 p-4">
                                    <summary class="cursor-pointer font-medium text-primary">Yeni varyant ekle</summary>
                                    <form method="POST" action="{{ route('admin.ecommerce.inventory.variants.store', $product) }}" class="mt-4 grid gap-3 lg:grid-cols-4">
                                        @csrf
                                        <input name="title" class="kt-input" placeholder="Varyant adı">
                                        <input name="sku" class="kt-input" placeholder="SKU">
                                        <input name="barcode" class="kt-input" placeholder="Barkod">
                                        <input name="option_values_text" class="kt-input" placeholder="Seçenekler: Mavi, L">
                                        <input name="stock" type="number" step="0.001" min="0" class="kt-input" placeholder="Stok">
                                        <input name="price" type="number" step="0.01" min="0" class="kt-input" placeholder="Fiyat">
                                        <input name="sale_price" type="number" step="0.01" min="0" class="kt-input" placeholder="İndirimli fiyat">
                                        <input name="currency" class="kt-input uppercase" value="TRY" maxlength="3" placeholder="TRY">
                                        <input name="low_stock_threshold" type="number" step="0.001" min="0" class="kt-input" placeholder="Düşük stok eşiği">
                                        <label class="flex items-center gap-2 text-sm text-muted-foreground">
                                            <input type="hidden" name="is_active" value="0">
                                            <input type="checkbox" name="is_active" value="1" class="kt-checkbox" checked>
                                            Aktif
                                        </label>
                                        <button type="submit" class="kt-btn kt-btn-primary lg:col-span-4">Varyantı Kaydet</button>
                                    </form>
                                </details>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-3xl border border-dashed border-border px-6 py-12 text-center">
                            <div class="text-lg font-semibold text-foreground">Ürün bulunamadı.</div>
                            <div class="mt-2 text-sm text-muted-foreground">Stok yönetimi için önce ürün kataloğuna kayıt ekleyin.</div>
                        </div>
                    @endforelse
                </div>

                @if($products->hasPages())
                    <div class="kt-card-footer">
                        {{ $products->links() }}
                    </div>
                @endif
            </div>

            <div class="kt-card self-start xl:sticky xl:top-6">
                <div class="kt-card-header py-5">
                    <div>
                        <h3 class="kt-card-title">Son Stok Hareketleri</h3>
                        <div class="text-sm text-muted-foreground">Stok defteri değişiklikleri.</div>
                    </div>
                </div>
                <div class="kt-card-content grid gap-3 p-5">
                    @forelse($movements as $movement)
                        <div class="rounded-2xl border border-border bg-background/70 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-medium text-foreground">
                                        {{ $movement->productVariant?->title ?: $movement->product?->title ?: 'Ürün silinmiş' }}
                                    </div>
                                    <div class="mt-1 text-sm text-muted-foreground">
                                        {{ $movementTypeOptions[$movement->type] ?? $movement->type }} · {{ number_format((float) $movement->quantity, 3, ',', '.') }}
                                    </div>
                                </div>
                                <span class="text-xs text-muted-foreground">{{ $movement->occurred_at?->format('d.m H:i') }}</span>
                            </div>
                            <div class="mt-2 text-xs text-muted-foreground">
                                {{ number_format((float) $movement->before_stock, 3, ',', '.') }} → {{ number_format((float) $movement->after_stock, 3, ',', '.') }}
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
                            Henüz stok hareketi yok.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
