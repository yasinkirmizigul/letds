@extends('admin.layouts.main.app')

@section('content')
    @php($isTrash = ($mode ?? 'active') === 'trash')

    <div class="kt-container-fixed max-w-[90%]"
         data-page="categories.index"
         data-mode="{{ $mode ?? 'active' }}"
         data-list-url="{{ route('admin.categories.list_legacy') }}"
         data-bulk-delete-url="{{ route('admin.categories.bulkDestroy') }}"
         data-bulk-restore-url="{{ route('admin.categories.bulkRestore') }}"
         data-bulk-force-delete-url="{{ route('admin.categories.bulkForceDestroy') }}">

        <div class="grid gap-5 lg:gap-7.5">

            @includeIf('admin.partials._flash')

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                <div class="kt-card">
                    <div class="kt-card-content p-5">
                        <div class="text-sm text-muted-foreground">Toplam kategori</div>
                        <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($stats['total'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="kt-card">
                    <div class="kt-card-content p-5">
                        <div class="text-sm text-muted-foreground">Kok kategori</div>
                        <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($stats['roots'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="kt-card">
                    <div class="kt-card-content p-5">
                        <div class="text-sm text-muted-foreground">Blog bagi</div>
                        <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($stats['blog_links'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="kt-card">
                    <div class="kt-card-content p-5">
                        <div class="text-sm text-muted-foreground">Proje bagi</div>
                        <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($stats['project_links'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="kt-card">
                    <div class="kt-card-content p-5">
                        <div class="text-sm text-muted-foreground">Urun bagi</div>
                        <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($stats['product_links'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="kt-card">
                    <div class="kt-card-content p-5">
                        <div class="text-sm text-muted-foreground">Cop kutusu</div>
                        <div class="mt-2 text-2xl font-semibold text-warning">{{ number_format((int) ($stats['trash'] ?? 0)) }}</div>
                    </div>
                </div>
            </div>

            <div class="kt-card kt-card-grid min-w-full">

                <div class="kt-card-header py-5 flex-wrap gap-4">
                    <div class="flex flex-col">
                        <h3 class="kt-card-title">{{ $pageTitle ?? 'Kategoriler' }}</h3>
                        <div class="text-sm text-muted-foreground">Blog, proje ve urun akislarinda kullanilan ortak kategori yapisi.</div>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">
                        <input
                            id="categoriesSearch"
                            type="text"
                            class="kt-input kt-input-sm w-[240px]"
                            placeholder="Kategori adi veya slug ara..." />

                        <a href="{{ route('admin.categories.index') }}"
                           class="kt-btn kt-btn-sm {{ $isTrash ? 'kt-btn-light' : 'kt-btn-primary' }}">
                            Kategoriler
                        </a>

                        @perm('categories.trash')
                            <a href="{{ route('admin.categories.trash') }}"
                               class="kt-btn kt-btn-sm {{ $isTrash ? 'kt-btn-primary' : 'kt-btn-light' }}">
                                Silinenler
                            </a>
                        @endperm

                        @if(!$isTrash)
                            @perm('categories.create')
                                <a href="{{ route('admin.categories.create') }}" class="kt-btn kt-btn-sm kt-btn-primary">
                                    Yeni Kategori
                                </a>
                            @endperm
                        @endif
                    </div>
                </div>

                <div class="kt-card-content">
                    <div class="grid" id="categories_dt">

                        <div id="categoriesBulkBar" class="hidden kt-card mb-4">
                            <div class="kt-card-content p-3 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" class="kt-checkbox kt-checkbox-sm" id="categories_check_all">
                                        <span>Tumunu sec</span>
                                    </label>
                                    <span class="text-sm text-muted-foreground">
                                        Secili: <b id="categoriesSelectedCount">0</b>
                                    </span>
                                </div>

                                <div class="flex items-center gap-2">
                                    @if($isTrash)
                                        @perm('categories.restore')
                                            <button type="button" class="kt-btn kt-btn-sm kt-btn-success" id="categoriesBulkRestoreBtn" disabled>
                                                <i class="ki-outline ki-arrow-circle-left"></i> Geri Yukle
                                            </button>
                                        @endperm

                                        @perm('categories.force_delete')
                                            <button type="button" class="kt-btn kt-btn-sm kt-btn-destructive" id="categoriesBulkForceDeleteBtn" disabled>
                                                <i class="ki-outline ki-trash"></i> Kalici Sil
                                            </button>
                                        @endperm
                                    @else
                                        @perm('categories.delete')
                                            <button type="button" class="kt-btn kt-btn-sm kt-btn-destructive" id="categoriesBulkDeleteBtn" disabled>
                                                <i class="ki-outline ki-trash"></i> Secilenleri Sil
                                            </button>
                                        @endperm
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="kt-scrollable-x-auto overflow-y-hidden">
                            <table id="categories_table"
                                   class="kt-table table-auto kt-table-border w-full"
                                   data-ajax="{{ route('admin.categories.list') }}">
                                <thead>
                                <tr>
                                    <th class="w-[55px] dt-orderable-none">
                                        <input class="kt-checkbox kt-checkbox-sm" id="categories_check_all_head" type="checkbox">
                                    </th>
                                    <th class="min-w-[260px]">Ad</th>
                                    <th class="min-w-[220px]">Slug</th>
                                    <th class="min-w-[220px]">Ust Kategori</th>
                                    <th class="min-w-[220px]">Baglantilar</th>
                                    <th class="w-[220px]"></th>
                                </tr>
                                </thead>
                                <tbody id="categoriesTbody"></tbody>
                            </table>
                        </div>

                        <template id="dt-empty-categories">
                            <tr data-kt-empty-row="true">
                                <td colspan="6" class="py-12">
                                    <div class="flex flex-col items-center text-center gap-2">
                                        <i class="ki-outline ki-folder text-3xl text-muted-foreground"></i>
                                        <div class="font-semibold">Henuz kategori yok</div>
                                        <div class="text-sm text-muted-foreground">Yeni bir kategori olustur.</div>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <template id="dt-zero-categories">
                            <tr data-kt-zero-row="true">
                                <td colspan="6" class="py-12">
                                    <div class="flex flex-col items-center text-center gap-2">
                                        <i class="ki-outline ki-magnifier text-3xl text-muted-foreground"></i>
                                        <div class="font-semibold">Sonuc bulunamadi</div>
                                        <div class="text-sm text-muted-foreground">Aramani degistirip tekrar dene.</div>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <div class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
                            <div class="flex items-center gap-2 order-2 md:order-1">
                                Goster
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
