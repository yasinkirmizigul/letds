{{-- resources/views/admin/pages/products/index.blade.php --}}
@extends('admin.layouts.main.app')

@section('content')
    @php
        // Controller’dan geliyor varsayıyorum (Project’teki gibi):
        // $mode: 'active'|'trash'
        // $products: LengthAwarePaginator veya Collection
        $isTrash = ($mode ?? 'active') === 'trash';

        $statusOptions = \App\Models\Admin\Product\Product::statusOptionsSorted();
    @endphp

    <div class="kt-container-fixed"
         data-page="{{ $isTrash ? 'products.trash' : 'products.index' }}"
         data-status-options='@json($statusOptions)'>

        <x-list-layout
            :title="$isTrash ? 'Silinmiş Ürünler' : 'Ürünler'"
            :searchAction="$isTrash ? route('admin.products.trash') : route('admin.products.index')">

            <x-slot:actions>
                @if($isTrash)
                    <a href="{{ route('admin.products.index') }}" class="kt-btn kt-btn-light">
                        Aktif Liste
                    </a>
                @else
                    <a href="{{ route('admin.products.trash') }}" class="kt-btn kt-btn-light">
                        <i class="ki-outline ki-trash"></i>
                        Çöp
                    </a>

                    @can('products.create')
                        <a href="{{ route('admin.products.create') }}" class="kt-btn kt-btn-primary">
                            <i class="ki-outline ki-plus"></i>
                            Yeni Ürün
                        </a>
                    @endcan
                @endif
            </x-slot:actions>

            <table id="products_table" class="kt-table table-auto kt-table-border w-full">
                <thead>
                <tr>
                    <th class="w-[44px] dt-orderable-none">
                        <input class="kt-checkbox kt-checkbox-sm" id="products_check_all" type="checkbox">
                    </th>

                    <th class="min-w-[340px]">Ürün</th>
                    <th class="min-w-[180px]">SKU</th>
                    <th class="min-w-[140px] text-right">Fiyat</th>
                    <th class="min-w-[110px] text-right">Stok</th>

                    <th class="min-w-[220px]">Kısa Bağlantı</th>
                    <th class="min-w-[200px]">Durum</th>
                    <th class="min-w-[140px] text-center">Anasayfa</th>
                    <th class="min-w-[170px] text-right">Tarih</th>
                    <th class="min-w-[130px] text-right dt-orderable-none">İşlemler</th>
                </tr>
                </thead>

                <tbody>
                @forelse($products as $p)
                    @php
                        $img = $p->featuredMediaUrl()
                            ?: ($p->featured_image_path ? asset('storage/'.$p->featured_image_path) : null);

                        $price = $p->sale_price ?? $p->price;
                        $cur   = $p->currency ?? 'TRY';

                        $st = $p->status ?? \App\Models\Admin\Product\Product::STATUS_APPOINTMENT_PENDING;
                    @endphp

                    <tr data-id="{{ $p->id }}">
                        <td>
                            <input class="kt-checkbox kt-checkbox-sm products-check" type="checkbox" value="{{ $p->id }}">
                        </td>

                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl overflow-hidden border border-border bg-muted/30 shrink-0">
                                    @if($img)
                                        <a href="javascript:void(0)"
                                           class="block w-full h-full js-img-popover"
                                           data-popover-img="{{ $img }}">
                                            <img src="{{ $img }}" class="w-full h-full object-cover" alt="">
                                        </a>
                                    @else
                                        <div class="w-full h-full grid place-items-center text-muted-foreground">
                                            <i class="ki-outline ki-picture text-lg"></i>
                                        </div>
                                    @endif
                                </div>

                                <div class="grid">
                                    @if($isTrash)
                                        <span class="font-semibold">{{ $p->title }}</span>
                                    @else
                                        <a class="font-semibold hover:underline"
                                           href="{{ route('admin.products.edit', $p->id) }}">
                                            {{ $p->title }}
                                        </a>
                                    @endif

                                    <div class="text-xs text-muted-foreground">#{{ $p->id }}</div>
                                </div>
                            </div>
                        </td>

                        <td class="text-sm text-muted-foreground">
                            {{ $p->sku ?: '—' }}
                        </td>

                        <td class="text-sm text-right whitespace-nowrap">
                            @if($price !== null)
                                <span class="font-medium">{{ number_format((float)$price, 2, ',', '.') }}</span>
                                <span class="text-muted-foreground">{{ $cur }}</span>
                            @else
                                <span class="text-muted-foreground">—</span>
                            @endif
                        </td>

                        <td class="text-sm text-right">
                            @if($p->stock !== null)
                                <span class="{{ ((int)$p->stock) <= 0 ? 'text-danger font-semibold' : '' }}">
                                {{ (int)$p->stock }}
                            </span>
                            @else
                                <span class="text-muted-foreground">—</span>
                            @endif
                        </td>

                        <td class="text-sm text-muted-foreground">
                            {{ $p->slug }}
                        </td>

                        <td>
                            <button type="button"
                                    class="{{ \App\Models\Admin\Product\Product::statusBadgeClass($st) }} js-status-trigger"
                                    data-product-id="{{ $p->id }}"
                                    data-status="{{ $st }}"
                                {{ $isTrash ? 'disabled' : '' }}>
                                {{ \App\Models\Admin\Product\Product::statusLabel($st) }}
                                <i class="ki-outline ki-down ml-1"></i>
                            </button>
                        </td>

                        <td class="text-center">
                            <div class="flex items-center justify-center gap-2">
                                <input type="checkbox"
                                       class="kt-switch kt-switch-sm js-featured-toggle"
                                       data-product-id="{{ $p->id }}"
                                    {{ $p->is_featured ? 'checked' : '' }}
                                    {{ $isTrash ? 'disabled' : '' }}>
                                <span class="kt-badge kt-badge-sm kt-badge-light js-featured-badge {{ $p->is_featured ? '' : 'hidden' }}">
                                Anasayfa
                            </span>
                            </div>
                        </td>

                        <td class="text-sm text-muted-foreground text-right whitespace-nowrap">
                            <div class="grid gap-0.5">
                                <span>{{ $p->updated_at?->format('d.m.Y') }}</span>
                                <span class="text-xs">{{ $p->updated_at?->format('H:i') }}</span>
                            </div>
                        </td>

                        <td class="text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-1">
                                @if($isTrash)
                                    @can('products.restore')
                                        <button type="button"
                                                class="kt-btn kt-btn-sm kt-btn-light"
                                                data-action="restore"
                                                data-id="{{ $p->id }}">
                                            Geri Al
                                        </button>
                                    @endcan

                                    @can('products.force_delete')
                                        <button type="button"
                                                class="kt-btn kt-btn-sm kt-btn-danger"
                                                data-action="force-delete"
                                                data-id="{{ $p->id }}">
                                            Kalıcı Sil
                                        </button>
                                    @endcan
                                @else
                                    @can('products.update')
                                        <a class="kt-btn kt-btn-sm kt-btn-light"
                                           href="{{ route('admin.products.edit', $p->id) }}">
                                            Düzenle
                                        </a>
                                    @endcan

                                    @can('products.delete')
                                        <button type="button"
                                                class="kt-btn kt-btn-sm kt-btn-danger"
                                                data-action="delete"
                                                data-id="{{ $p->id }}">
                                            Sil
                                        </button>
                                    @endcan
                                @endif
                            </div>
                        </td>
                    </tr>

                @empty
                    <tr>
                        <td colspan="100%">
                            <div class="grid place-items-center py-14 gap-3 text-muted-foreground">
                                <i class="ki-outline ki-folder text-3xl"></i>
                                <div class="text-sm">{{ $isTrash ? 'Silinmiş kayıt yok' : 'Kayıt yok' }}</div>

                                @if(!$isTrash)
                                    @can('products.create')
                                        <a href="{{ route('admin.products.create') }}"
                                           class="kt-btn kt-btn-sm kt-btn-primary">
                                            Yeni ürün oluştur
                                        </a>
                                    @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>

        </x-list-layout>
    </div>
@endsection
