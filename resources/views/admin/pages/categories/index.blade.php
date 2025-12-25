@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[90%]" data-page="categories.index">
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

                        @perm('category.create')
                            <a href="{{ route('admin.categories.create') }}" class="kt-btn kt-btn-sm kt-btn-primary">
                                Yeni Kategori
                            </a>
                        @endperm
                    </div>
                </div>

                <div class="kt-card-content">
                    <div class="grid" id="categories_dt">

                        <div class="overflow-hidden">
                            <table class="kt-table table-auto kt-table-border w-full" id="categories_table">
                                <thead>
                                <tr>
                                    <th class="min-w-[260px]">Ad</th>
                                    <th class="min-w-[240px]">Slug</th>
                                    <th class="min-w-[220px]">Üst Kategori</th>
                                    <th class="w-[90px]">Blog</th>
                                    <th class="w-[170px]"></th>
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($categories as $cat)
                                    <tr>
                                        <td class="font-medium">{{ $cat->name }}</td>
                                        <td class="text-sm text-muted-foreground">{{ $cat->slug }}</td>
                                        <td class="text-sm text-muted-foreground">{{ $cat->parent?->name ?? '-' }}</td>
                                        <td class="text-sm text-secondary-foreground">{{ $cat->blog_posts_count ?? 0 }}</td>
                                        <td>
                                            <div class="inline-flex gap-2 justify-end">
                                                @perm('category.update')
                                                    <a class="kt-btn kt-btn-light kt-btn-sm"
                                                       href="{{ route('admin.categories.edit', ['category' => $cat->id]) }}">
                                                        Düzenle
                                                    </a>
                                                @endperm

                                                @perm('category.delete')
                                                    <form method="POST"
                                                          action="{{ route('admin.categories.destroy', ['category' => $cat->id]) }}"
                                                          onsubmit="return confirm('Kategori silinsin mi? (İlişkiler otomatik kaldırılır)')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="kt-btn kt-btn-destructive kt-btn-sm">Sil</button>
                                                    </form>
                                                @endperm
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- EMPTY TEMPLATE (hiç kayıt yok) --}}
                        <template id="dt-empty-categories">
                            <tr data-kt-empty-row="true">
                                <td colspan="5" class="py-12">
                                    <div class="flex flex-col items-center text-center gap-2">
                                        <i class="ki-outline ki-folder text-3xl text-muted-foreground"></i>
                                        <div class="font-semibold">Henüz kategori yok</div>
                                        <div class="text-sm text-muted-foreground">Yeni bir kategori oluştur.</div>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        {{-- ZERO TEMPLATE (arama sonucu yok) --}}
                        <template id="dt-zero-categories">
                            <tr data-kt-zero-row="true">
                                <td colspan="5" class="py-12">
                                    <div class="flex flex-col items-center text-center gap-2">
                                        <i class="ki-outline ki-magnifier text-3xl text-muted-foreground"></i>
                                        <div class="font-semibold">Sonuç bulunamadı</div>
                                        <div class="text-sm text-muted-foreground">Aramanı değiştirip tekrar dene.</div>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        {{-- FOOTER (permission/user index ile aynı) --}}
                        <div class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
                            <div class="flex items-center gap-2 order-2 md:order-1">
                                Göster
                                <select class="kt-select w-16" id="categoriesPageSize" name="perpage"></select>
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

