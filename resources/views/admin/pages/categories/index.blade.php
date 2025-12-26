@extends('admin.layouts.main.app')

@section('content')
    @php($isTrash = ($mode ?? 'active') === 'trash')

    <div class="kt-container-fixed max-w-[90%]"
         data-page="categories.index"
         data-mode="{{ $mode ?? 'active' }}"
         data-perpage="25">

        <div class="grid gap-5 lg:gap-7.5">

            @includeIf('admin.partials._flash')

            <div class="kt-card kt-card-grid min-w-full">

                <div class="kt-card-header py-5 flex-wrap gap-4">
                    <div class="flex flex-col">
                        <h3 class="kt-card-title">{{ $pageTitle ?? 'Kategoriler' }}</h3>
                        <div class="text-sm text-muted-foreground">Ortak kategori sistemi (blog/galeri/ürün)</div>
                    </div>

                    <div class="flex items-center gap-2">

                        <input
                            id="categoriesSearch"
                            type="text"
                            class="kt-input kt-input-sm"
                            placeholder="Kategori adı / slug ara..."
                        />

                        {{-- Toggle: Aktif / Silinenler --}}
                        <a href="{{ route('admin.categories.index') }}"
                           class="kt-btn kt-btn-sm {{ $isTrash ? 'kt-btn-light' : 'kt-btn-primary' }}">
                            Kategoriler
                        </a>

                        @perm('category.trash')
                        <a href="{{ route('admin.categories.trash') }}"
                           class="kt-btn kt-btn-sm {{ $isTrash ? 'kt-btn-primary' : 'kt-btn-light' }}">
                            Silinenler
                        </a>
                        @endperm

                        @perm('category.create')
                        <a href="{{ route('admin.categories.create') }}" class="kt-btn kt-btn-sm kt-btn-primary">
                            Yeni Kategori
                        </a>
                        @endperm
                    </div>
                </div>

                <div class="kt-card-content">
                    <div class="grid" id="categories_dt">

                        {{-- Bulk bar --}}
                        <div id="categoriesBulkBar" class="hidden kt-card mb-4">
                            <div class="kt-card-content p-3 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" class="kt-checkbox kt-checkbox-sm" id="categories_check_all">
                                        <span>Tümünü seç</span>
                                    </label>
                                    <span class="text-sm text-muted-foreground">
                                        Seçili: <b id="categoriesSelectedCount">0</b>
                                    </span>
                                </div>

                                <div class="flex items-center gap-2">
                                    @if($isTrash)
                                        @perm('category.restore')
                                        <button type="button" class="kt-btn kt-btn-sm kt-btn-success" id="categoriesBulkRestoreBtn" disabled>
                                            <i class="ki-outline ki-arrow-circle-left"></i> Geri Yükle
                                        </button>
                                        @endperm

                                        @perm('category.force_delete')
                                        <button type="button" class="kt-btn kt-btn-sm kt-btn-destructive" id="categoriesBulkForceDeleteBtn" disabled>
                                            <i class="ki-outline ki-trash"></i> Kalıcı Sil
                                        </button>
                                        @endperm
                                    @else
                                        @perm('category.delete')
                                        <button type="button" class="kt-btn kt-btn-sm kt-btn-destructive" id="categoriesBulkDeleteBtn" disabled>
                                            <i class="ki-outline ki-trash"></i> Seçilenleri Sil
                                        </button>
                                        @endperm
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="overflow-hidden">
                            <table class="kt-table table-auto kt-table-border w-full" id="categories_table">
                                <thead>
                                <tr>
                                    <th class="w-[55px] dt-orderable-none">
                                        <input class="kt-checkbox kt-checkbox-sm" id="categories_check_all_head" type="checkbox">
                                    </th>
                                    <th class="min-w-[260px]">Ad</th>
                                    <th class="min-w-[240px]">Slug</th>
                                    <th class="min-w-[220px]">Üst Kategori</th>
                                    <th class="w-[90px]">Blog</th>
                                    <th class="w-[220px]"></th>
                                </tr>
                                </thead>
                                <tbody id="categoriesTbody"></tbody>
                            </table>
                        </div>

                        {{-- Empty / Zero templates --}}
                        <template id="dt-empty-categories">
                            <tr data-kt-empty-row="true">
                                <td colspan="6" class="py-12">
                                    <div class="flex flex-col items-center text-center gap-2">
                                        <i class="ki-outline ki-folder text-3xl text-muted-foreground"></i>
                                        <div class="font-semibold">Henüz kategori yok</div>
                                        <div class="text-sm text-muted-foreground">Yeni bir kategori oluştur.</div>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <template id="dt-zero-categories">
                            <tr data-kt-zero-row="true">
                                <td colspan="6" class="py-12">
                                    <div class="flex flex-col items-center text-center gap-2">
                                        <i class="ki-outline ki-magnifier text-3xl text-muted-foreground"></i>
                                        <div class="font-semibold">Sonuç bulunamadı</div>
                                        <div class="text-sm text-muted-foreground">Aramanı değiştirip tekrar dene.</div>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        {{-- Footer (client pagination) --}}
                        <div class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
                            <div class="flex items-center gap-2 order-2 md:order-1">
                                Göster
                                <select class="kt-select w-16" id="categoriesPageSize" data-kt-select="true"></select>
                                / sayfa
                            </div>

                            <div class="flex items-center gap-4 order-1 md:order-2">
                                <span id="categoriesInfo"></span>
                                <div class="kt-datatable-pagination" id="categoriesPagination"></div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

        </div>
    </div>
@endsection
