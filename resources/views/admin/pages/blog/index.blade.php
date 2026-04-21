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
        data-page="blog.index"
        data-mode="{{ $mode }}"
        data-perpage="{{ $perPage ?? 25 }}"
        data-bulk-delete-url="{{ route('admin.blog.bulkDestroy') }}"
        data-bulk-restore-url="{{ route('admin.blog.bulkRestore') }}"
        data-bulk-force-delete-url="{{ route('admin.blog.bulkForceDestroy') }}"
    >
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-xl font-semibold">
                    {{ $isTrash ? 'Blog Cop Kutusu' : 'Blog Yonetimi' }}
                </h1>
                <div class="text-sm text-muted-foreground">
                    {{ $isTrash ? 'Silinen yazilari geri yukleyebilir veya kalici olarak silebilirsiniz.' : 'Icerik, yayin akisi ve vitrin secimlerini tek ekrandan yonetin.' }}
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('admin.blog.index') }}"
                    class="kt-btn kt-btn-sm {{ $isTrash ? 'kt-btn-light' : 'kt-btn-primary' }}"
                >
                    Aktif Yazilar
                </a>
                <a
                    href="{{ route('admin.blog.trash') }}"
                    class="kt-btn kt-btn-sm {{ $isTrash ? 'kt-btn-primary' : 'kt-btn-light' }}"
                >
                    Cop Kutusu
                </a>

                @perm('blog.create')
                    <a href="{{ route('admin.blog.create') }}" class="kt-btn kt-btn-sm kt-btn-primary">
                        Yeni Yazi
                    </a>
                @endperm
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-3xl border border-border bg-white p-5 shadow-sm">
                <div class="text-sm text-muted-foreground">Toplam Yazi</div>
                <div class="mt-2 text-3xl font-semibold text-foreground">{{ $stats['all'] ?? 0 }}</div>
            </div>
            <div class="rounded-3xl border border-border bg-white p-5 shadow-sm">
                <div class="text-sm text-muted-foreground">Yayinda</div>
                <div class="mt-2 text-3xl font-semibold text-success">{{ $stats['published'] ?? 0 }}</div>
            </div>
            <div class="rounded-3xl border border-border bg-white p-5 shadow-sm">
                <div class="text-sm text-muted-foreground">Taslak</div>
                <div class="mt-2 text-3xl font-semibold text-warning">{{ $stats['draft'] ?? 0 }}</div>
            </div>
            <div class="rounded-3xl border border-border bg-white p-5 shadow-sm">
                <div class="text-sm text-muted-foreground">Anasayfada</div>
                <div class="mt-2 text-3xl font-semibold text-primary">{{ $stats['featured'] ?? 0 }}</div>
            </div>
            <div class="rounded-3xl border border-border bg-white p-5 shadow-sm">
                <div class="text-sm text-muted-foreground">Copte</div>
                <div class="mt-2 text-3xl font-semibold text-danger">{{ $stats['trash'] ?? 0 }}</div>
            </div>
        </div>

        <div class="kt-card kt-card-grid min-w-full">
            <div class="kt-card-header py-5 flex-wrap gap-4">
                <div>
                    <h3 class="kt-card-title">{{ $isTrash ? 'Silinen Yazilar' : 'Yazi Listesi' }}</h3>
                    <div class="text-sm text-muted-foreground">
                        Durum, kategori, SEO yeterliligi ve son guncelleme bilgisini tek satirda gorebilirsiniz.
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <input
                        id="blogSearch"
                        type="text"
                        class="kt-input kt-input-sm w-full md:w-[260px]"
                        placeholder="Baslik, slug, ozet ara..."
                        value="{{ $q }}"
                    />

                    <select
                        id="blogStatusFilter"
                        class="kt-select w-full md:w-[180px]"
                        data-kt-select="true"
                        data-kt-select-placeholder="Durum"
                    >
                        <option value="all" @selected($status === 'all')>Tum durumlar</option>
                        <option value="published" @selected($status === 'published')>Yayinda</option>
                        <option value="draft" @selected($status === 'draft')>Taslak</option>
                        <option value="featured" @selected($status === 'featured')>Anasayfada</option>
                    </select>

                    <select
                        id="blogCategoryFilter"
                        class="kt-select w-full md:w-[250px]"
                        multiple
                        data-kt-select="true"
                        data-kt-select-placeholder="Kategoriler"
                        data-kt-select-multiple="true"
                        data-kt-select-tags="false"
                        data-kt-select-config='{"showSelectedCount":true,"enableSelectAll":true,"selectAllText":"Tumunu Sec","clearAllText":"Temizle"}'
                    >
                        @foreach(($categoryOptions ?? []) as $option)
                            <option value="{{ $option['id'] }}" @selected(in_array($option['id'], $selectedCategoryIds))>
                                {{ $option['label'] }}
                            </option>
                        @endforeach
                    </select>

                    <button type="button" id="blogClearFiltersBtn" class="kt-btn kt-btn-sm kt-btn-light">
                        Filtreleri Temizle
                    </button>
                </div>
            </div>

            <div class="kt-card-content">
                <div id="blogBulkBar" class="hidden kt-card mb-4 border border-border">
                    <div class="kt-card-content p-3 flex items-center justify-between gap-3">
                        <div class="text-sm text-muted-foreground">
                            Secili: <b id="blogSelectedCount">0</b>
                        </div>

                        <div class="flex items-center gap-2">
                            @if($isTrash)
                                @perm('blog.restore')
                                    <button type="button" class="kt-btn kt-btn-sm kt-btn-success" id="blogBulkRestoreBtn" disabled>
                                        Geri Yukle
                                    </button>
                                @endperm
                                @perm('blog.force_delete')
                                    <button type="button" class="kt-btn kt-btn-sm kt-btn-destructive" id="blogBulkForceDeleteBtn" disabled>
                                        Kalici Sil
                                    </button>
                                @endperm
                            @else
                                @perm('blog.delete')
                                    <button type="button" class="kt-btn kt-btn-sm kt-btn-destructive" id="blogBulkDeleteBtn" disabled>
                                        Sil
                                    </button>
                                @endperm
                            @endif
                        </div>
                    </div>
                </div>

                <div class="grid" id="blog_dt">
                    <div class="kt-scrollable-x-auto overflow-y-hidden">
                        <table class="kt-table table-auto kt-table-border w-full" id="blog_table">
                            <thead>
                            <tr>
                                <th class="w-[55px] dt-orderable-none">
                                    <input class="kt-checkbox kt-checkbox-sm" id="blog_check_all" type="checkbox">
                                </th>
                                <th class="min-w-[360px]">Yazi</th>
                                <th class="min-w-[280px]">URL ve SEO</th>
                                <th class="min-w-[230px]">Durum</th>
                                <th class="min-w-[220px]">Anasayfa</th>
                                <th class="min-w-[180px]">Son Guncelleme</th>
                                <th class="w-[64px]"></th>
                                <th class="w-[72px]"></th>
                            </tr>
                            </thead>

                            <tbody>
                            @foreach($posts as $post)
                                @php
                                    $img = $post->featuredMediaUrl() ?: $post->featured_image_url;
                                    $seoScore = $post->seoCompletenessScore();
                                    $readTime = $post->estimatedReadTimeMinutes();
                                    $categoryIdsAttr = '|' . $post->categories->pluck('id')->map(fn ($id) => (int) $id)->implode('|') . '|';
                                @endphp

                                <tr
                                    data-row-id="{{ $post->id }}"
                                    data-published="{{ $post->is_published ? '1' : '0' }}"
                                    data-featured="{{ $post->is_featured ? '1' : '0' }}"
                                    data-category-ids="{{ $categoryIdsAttr }}"
                                >
                                    <td class="w-[55px]">
                                        <input class="kt-checkbox kt-checkbox-sm blog-check" type="checkbox" value="{{ $post->id }}">
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
                                                        <img src="{{ $img }}" alt="" class="w-full h-full object-cover">
                                                    </a>
                                                @else
                                                    <div class="w-full h-full grid place-items-center text-muted-foreground">
                                                        <i class="ki-outline ki-picture text-xl"></i>
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="grid gap-2 min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <a
                                                        href="{{ route('admin.blog.edit', $post) }}"
                                                        class="font-semibold text-foreground hover:text-primary"
                                                    >
                                                        {{ $post->title }}
                                                    </a>
                                                    <span class="kt-badge kt-badge-sm kt-badge-light">#{{ $post->id }}</span>
                                                    <span class="kt-badge kt-badge-sm {{ $seoScore >= 80 ? 'kt-badge-light-success' : ($seoScore >= 50 ? 'kt-badge-light-warning' : 'kt-badge-light-danger') }}">
                                                        SEO %{{ $seoScore }}
                                                    </span>
                                                    <span class="kt-badge kt-badge-sm kt-badge-light">
                                                        {{ $readTime > 0 ? $readTime . ' dk okuma' : 'Kisa yazi' }}
                                                    </span>
                                                </div>

                                                <div class="text-sm text-muted-foreground leading-6">
                                                    {{ $post->excerptPreview(130) ?: 'Ozet bulunmuyor.' }}
                                                </div>

                                                <div class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                                    <span>Yazar: {{ $post->author?->name ?? 'Belirlenmedi' }}</span>
                                                    @if($post->categories->isNotEmpty())
                                                        <span class="text-border">|</span>
                                                        @foreach($post->categories as $category)
                                                            <span class="kt-badge kt-badge-sm kt-badge-light">{{ $category->name }}</span>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="grid gap-2">
                                            <div class="text-sm font-medium text-foreground break-all">
                                                /blog/{{ $post->slug }}
                                            </div>
                                            <div class="text-sm text-muted-foreground">
                                                {{ $post->meta_title ?: 'Meta baslik girilmemis.' }}
                                            </div>
                                            <div class="text-xs text-muted-foreground leading-5">
                                                {{ \Illuminate\Support\Str::limit($post->meta_description ?: $post->excerptPreview(120), 120) }}
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="grid gap-2">
                                                <div class="js-badge">
                                                    @if($post->is_published)
                                                        <span class="kt-badge kt-badge-sm kt-badge-success">Yayinda</span>
                                                    @else
                                                        <span class="kt-badge kt-badge-sm kt-badge-light">Taslak</span>
                                                    @endif
                                                </div>
                                                <div class="text-xs text-muted-foreground js-published-at">
                                                    {{ $post->published_at ? 'Yayin: ' . $post->published_at->format('d.m.Y H:i') : 'Yayin tarihi yok' }}
                                                </div>
                                            </div>

                                            @perm('blog.update')
                                                <label class="kt-switch kt-switch-sm">
                                                    <input
                                                        class="js-publish-toggle kt-switch kt-switch-mono"
                                                        type="checkbox"
                                                        data-url="{{ route('admin.blog.togglePublish', $post) }}"
                                                        @checked($post->is_published)
                                                    />
                                                </label>
                                            @endperm
                                        </div>
                                    </td>

                                    <td>
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="grid gap-2">
                                                <div class="js-featured-badge">
                                                    @if($post->is_featured)
                                                        <span class="kt-badge kt-badge-sm kt-badge-light-success">Anasayfada</span>
                                                    @else
                                                        <span class="kt-badge kt-badge-sm kt-badge-light text-muted-foreground">Kapali</span>
                                                    @endif
                                                </div>
                                                <div class="text-xs text-muted-foreground js-featured-at">
                                                    {{ $post->featured_at ? 'Secim: ' . $post->featured_at->format('d.m.Y H:i') : 'Secili degil' }}
                                                </div>
                                            </div>

                                            @perm('blog.update')
                                                <label class="kt-switch kt-switch-sm">
                                                    <input
                                                        class="js-featured-toggle kt-switch kt-switch-mono"
                                                        type="checkbox"
                                                        data-url="{{ route('admin.blog.toggleFeatured', $post) }}"
                                                        @checked($post->is_featured)
                                                    />
                                                </label>
                                            @endperm
                                        </div>
                                    </td>

                                    <td data-order="{{ $post->updated_at?->timestamp ?? 0 }}">
                                        <div class="grid gap-1 text-sm">
                                            <span class="font-medium text-foreground">{{ $post->updated_at?->format('d.m.Y H:i') ?: '-' }}</span>
                                            <span class="text-muted-foreground">{{ $post->editor?->name ?: 'Editor bilgisi yok' }}</span>
                                        </div>
                                    </td>

                                    <td class="text-end">
                                        @perm('blog.update')
                                            <a
                                                href="{{ route('admin.blog.edit', $post) }}"
                                                class="kt-btn kt-btn-sm kt-btn-icon kt-btn-warning"
                                                title="Duzenle"
                                            >
                                                <i class="ki-filled ki-notepad-edit"></i>
                                            </a>
                                        @endperm
                                    </td>

                                    <td class="text-end">
                                        <div class="flex justify-end gap-1">
                                            @if($isTrash)
                                                @perm('blog.restore')
                                                    <button
                                                        type="button"
                                                        class="kt-btn kt-btn-sm kt-btn-success"
                                                        data-action="restore"
                                                        data-url="{{ route('admin.blog.restore', $post->id) }}"
                                                    >
                                                        <i class="ki-outline ki-arrow-circle-left"></i>
                                                    </button>
                                                @endperm

                                                @perm('blog.force_delete')
                                                    <button
                                                        type="button"
                                                        class="kt-btn kt-btn-sm kt-btn-destructive"
                                                        data-action="force-delete"
                                                        data-url="{{ route('admin.blog.forceDestroy', $post->id) }}"
                                                    >
                                                        <i class="ki-outline ki-trash"></i>
                                                    </button>
                                                @endperm
                                            @else
                                                @perm('blog.delete')
                                                    <button
                                                        type="button"
                                                        class="kt-btn kt-btn-sm kt-btn-destructive"
                                                        data-action="delete"
                                                        data-url="{{ route('admin.blog.destroy', $post) }}"
                                                    >
                                                        <i class="ki-outline ki-trash"></i>
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

                    <template id="dt-empty-blog">
                        <tr data-kt-empty-row="true">
                            <td colspan="8" class="py-12">
                                <div class="flex flex-col items-center text-center gap-2">
                                    <i class="ki-outline ki-document text-3xl text-muted-foreground"></i>
                                    <div class="font-semibold">Henuz blog yazisi bulunmuyor.</div>
                                    <div class="text-sm text-muted-foreground">Yeni bir yazi olusturarak baslayabilirsiniz.</div>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template id="dt-zero-blog">
                        <tr data-kt-zero-row="true">
                            <td colspan="8" class="py-12">
                                <div class="flex flex-col items-center text-center gap-2">
                                    <i class="ki-outline ki-magnifier text-3xl text-muted-foreground"></i>
                                    <div class="font-semibold">Filtreye uygun yazi bulunamadi.</div>
                                    <div class="text-sm text-muted-foreground">Arama ya da filtre secimlerini degistirip tekrar deneyin.</div>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <div class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
                        <div class="flex items-center gap-2 order-2 md:order-1">
                            Goster
                            <select class="kt-select w-16" id="blogPageSize" data-kt-select="true"></select>
                            / sayfa
                        </div>

                        <div class="flex items-center gap-4 order-1 md:order-2">
                            <span id="blogInfo"></span>
                            <div class="kt-datatable-pagination" id="blogPagination"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
