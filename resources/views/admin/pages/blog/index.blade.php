@extends('admin.layouts.main.app')

@section('content')
    @php
        $mode = $mode ?? 'active';
        $isTrash = $mode === 'trash';

        $q = $q ?? '';
        $selectedCategoryIds = $selectedCategoryIds ?? [];
        $hasFilter = ($q !== '') || !empty($selectedCategoryIds);
    @endphp

    <div class="kt-container-fixed max-w-[90%]"
         data-page="blog.index"
         data-mode="{{ $mode }}"
         data-perpage="{{ $perPage ?? 25 }}">

        <div class="grid gap-5 lg:gap-7.5">

            @includeIf('admin.partials._flash')

            <div class="kt-card kt-card-grid min-w-full">

                <div class="kt-card-header py-5 flex-wrap gap-4">
                    <div class="flex flex-col">
                        <h3 class="kt-card-title">
                            {{ $isTrash ? 'Silinen Blog Yazıları' : 'Blog Yazıları' }}
                        </h3>
                        <div class="text-sm text-muted-foreground">Blog yazılarını yönetin</div>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">

                        {{-- Filtre --}}
                        <form method="GET"
                              data-blog-filter-form="true"
                              action="{{ $isTrash ? route('admin.blog.trash') : route('admin.blog.index') }}"
                              class="flex items-center gap-2">

                            <input
                                id="blogSearch"
                                name="q"
                                type="text"
                                class="kt-input kt-input-sm"
                                placeholder="Başlık / kısa bağlantı ara..."
                                value="{{ $q }}"
                            />

                            <select
                                id="blogCategoryFilter"
                                name="category_ids[]"
                                class="kt-select w-full"
                                data-kt-select="true"
                                data-kt-select-placeholder="Kategoriler"
                                data-kt-select-multiple="true"
                                data-kt-select-tags="false"
                                data-kt-select-config='{"showSelectedCount": true}'
                            >
                                @foreach(($categoryOptions ?? []) as $opt)
                                    <option value="{{ $opt['id'] }}"
                                        @selected(in_array($opt['id'], $selectedCategoryIds, true))>
                                        {{ $opt['label'] }}
                                    </option>
                                @endforeach
                            </select>

                            <button type="submit" class="kt-btn kt-btn-sm kt-btn-light">Filtrele</button>

                            @if($hasFilter)
                                <a href="{{ $isTrash ? route('admin.blog.trash') : route('admin.blog.index') }}"
                                   class="kt-btn kt-btn-sm kt-btn-light">Temizle</a>
                            @endif
                        </form>

                        {{-- Toggle: Blog / Silinenler --}}
                        <a href="{{ route('admin.blog.index') }}"
                           class="kt-btn kt-btn-sm {{ $isTrash ? 'kt-btn-light' : 'kt-btn-primary' }}">
                            Blog
                        </a>

                        <a href="{{ route('admin.blog.trash') }}"
                           class="kt-btn kt-btn-sm {{ $isTrash ? 'kt-btn-primary' : 'kt-btn-light' }}">
                            Silinenler
                        </a>

                        @perm('blog.create')
                        <a href="{{ route('admin.blog.create') }}" class="kt-btn kt-btn-sm kt-btn-primary">Yeni Yazı</a>
                        @endperm
                    </div>
                </div>

                <div class="kt-card-content">
                    <div class="grid" id="blog_dt">

                        {{-- Bulk bar (tek) --}}
                        <div id="blogBulkBar" class="hidden kt-card mb-4">
                            <div class="kt-card-content p-3 flex items-center justify-between gap-3">
                                <div class="text-sm text-muted-foreground">
                                    Seçili: <b id="blogSelectedCount">0</b>
                                </div>

                                <div class="flex items-center gap-2">
                                    @if($isTrash)
                                        <button type="button" class="kt-btn kt-btn-sm kt-btn-success" id="blogBulkRestoreBtn" disabled>
                                            <i class="ki-outline ki-arrow-circle-left"></i> Geri Yükle
                                        </button>
                                        <button type="button" class="kt-btn kt-btn-sm kt-btn-destructive" id="blogBulkForceDeleteBtn" disabled>
                                            <i class="ki-outline ki-trash"></i> Kalıcı Sil
                                        </button>
                                    @else
                                        <button type="button" class="kt-btn kt-btn-sm kt-btn-destructive" id="blogBulkDeleteBtn" disabled>
                                            <i class="ki-outline ki-trash"></i> Sil
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="kt-scrollable-x-auto overflow-y-hidden">
                            <table class="kt-table table-auto kt-table-border w-full" id="blog_table">
                                <thead>
                                <tr>
                                    <th class="w-[55px] dt-orderable-none">
                                        <input class="kt-checkbox kt-checkbox-sm" id="blog_check_all" type="checkbox">
                                    </th>
                                    <th class="w-[80px]">ID</th>
                                    <th class="min-w-[360px]">Yazı</th>
                                    <th class="min-w-[280px]">Kısa Bağlantı</th>
                                    <th class="min-w-[280px]">Durum</th>
                                    <th class="w-[200px] text-center">Anasayfa</th>
                                    <th class="min-w-[190px]">Güncelleme Tarihi</th>
                                    <th class="w-[60px]"></th>
                                    <th class="w-[60px]"></th>
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($posts as $p)
                                    @php
                                        $img = $p->featuredMediaUrl() ?: ($p->featured_image_path ? asset('storage/'.$p->featured_image_path) : null);
                                    @endphp

                                    <tr data-row-id="{{ $p->id }}">
                                        <td class="w-[55px]">
                                            <input class="kt-checkbox kt-checkbox-sm blog-check" type="checkbox" value="{{ $p->id }}">
                                        </td>

                                        <td class="text-sm text-secondary-foreground">{{ $p->id }}</td>

                                        <td>
                                            <div class="flex items-center gap-3">
                                                <div class="size-[44px] rounded-full overflow-hidden bg-muted flex items-center justify-center">
                                                    @if($img)
                                                        <a href="javascript:void(0)"
                                                           class="js-img-popover block size-full"
                                                           data-popover-img="{{ $img }}">
                                                            <img src="{{ $img }}" alt="" class="size-full object-cover"/>
                                                        </a>
                                                    @else
                                                        <i class="ki-outline ki-picture text-muted-foreground text-lg"></i>
                                                    @endif
                                                </div>

                                                <div class="flex flex-col gap-0.5">
                                                    <span class="font-semibold">{{ $p->title }}</span>
                                                    <span class="text-sm text-muted-foreground">{{ $p->author?->name ?? '-' }}</span>

                                                    @if($p->categories?->count())
                                                        <div class="flex flex-wrap gap-1 mt-1">
                                                            @foreach($p->categories as $c)
                                                                <span class="kt-badge kt-badge-sm kt-badge-light">{{ $c->name }}</span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>

                                        <td class="text-sm text-secondary-foreground">{{ $p->slug }}</td>

                                        <td>
                                            <div class="flex items-center justify-between gap-3">
                                                <div class="js-badge">
                                                    @if($p->is_published)
                                                        <span class="kt-badge kt-badge-sm kt-badge-success">Yayında</span>
                                                    @else
                                                        <span class="kt-badge kt-badge-sm kt-badge-light">Taslak</span>
                                                    @endif
                                                </div>

                                                @perm('blog.update')
                                                <label class="kt-switch kt-switch-sm">
                                                    <input
                                                        class="js-publish-toggle kt-switch kt-switch-mono"
                                                        type="checkbox"
                                                        data-url="{{ route('admin.blog.togglePublish', $p) }}"
                                                        @checked($p->is_published)
                                                    />
                                                </label>
                                                @endperm
                                            </div>

                                            <div class="text-sm text-muted-foreground mt-1 js-published-at">
                                                @if($p->published_at)
                                                    Yayın Tarihi: {{ $p->published_at->format('d.m.Y H:i') }}
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex flex-col gap-1">
                                                <div class="flex items-center justify-between gap-3">
                                                    <div class="js-featured-badge">
                                                        @if($p->is_featured)
                                                            <span class="kt-badge kt-badge-sm kt-badge-light-success">Anasayfada</span>
                                                        @else
                                                            <span class="kt-badge kt-badge-sm kt-badge-light text-muted-foreground">Kapalı</span>
                                                        @endif
                                                    </div>

                                                    @perm('blog.update')
                                                    <label class="kt-switch kt-switch-sm">
                                                        <input
                                                            class="js-featured-toggle kt-switch kt-switch-mono"
                                                            type="checkbox"
                                                            data-url="{{ route('admin.blog.toggleFeatured', $p) }}"
                                                            @checked($p->is_featured)
                                                        />
                                                    </label>
                                                    @endperm
                                                </div>

                                                <div class="text-sm text-muted-foreground mt-1 js-featured-at">
                                                    @if($p->featured_at)
                                                        Seçim: {{ $p->featured_at->format('d.m.Y H:i') }}
                                                    @endif
                                                </div>
                                            </div>
                                        </td>

                                        <td class="text-sm text-secondary-foreground">
                                            {{ $p->updated_at?->format('d.m.Y H:i') }}
                                        </td>

                                        {{-- Edit --}}
                                        <td class="text-end">
                                            @perm('blog.update')
                                            <a href="{{ route('admin.blog.edit', $p) }}"
                                               class="kt-btn kt-btn-sm kt-btn-icon kt-btn-warning"
                                               title="Düzenle">
                                                <i class="ki-filled ki-notepad-edit"></i>
                                            </a>
                                            @endperm
                                        </td>

                                        {{-- Actions --}}
                                        <td class="text-end">
                                            <div class="flex">
                                                @if($isTrash)
                                                    @perm('blog.restore')
                                                    <button type="button"
                                                            class="kt-btn kt-btn-sm kt-btn-success me-2.5"
                                                            data-action="restore"
                                                            data-id="{{ $p->id }}">
                                                        <i class="ki-outline ki-arrow-circle-left"></i>
                                                    </button>
                                                    @endperm

                                                    @perm('blog.force_delete')
                                                    <button type="button"
                                                            class="kt-btn kt-btn-sm kt-btn-destructive"
                                                            data-action="force-delete"
                                                            data-id="{{ $p->id }}">
                                                        <i class="ki-outline ki-trash"></i>
                                                    </button>
                                                    @endperm
                                                @else
                                                    @perm('blog.delete')
                                                    <button type="button"
                                                            class="kt-btn kt-btn-sm kt-btn-destructive"
                                                            data-action="delete"
                                                            data-id="{{ $p->id }}">
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
                                        <div class="font-semibold">Henüz blog yazısı yok</div>
                                        <div class="text-sm text-muted-foreground">Yeni yazı oluştur.</div>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <template id="dt-zero-blog">
                            <tr data-kt-zero-row="true">
                                <td colspan="8" class="py-12">
                                    <div class="flex flex-col items-center text-center gap-2">
                                        <i class="ki-outline ki-magnifier text-3xl text-muted-foreground"></i>
                                        <div class="font-semibold">Sonuç bulunamadı</div>
                                        <div class="text-sm text-muted-foreground">Aramanı değiştirip tekrar dene.</div>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <div class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
                            <div class="flex items-center gap-2 order-2 md:order-1">
                                Göster
                                <select class="kt-select w-16" id="blogPageSize" name="perpage" data-kt-select="true"></select>
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
    </div>
@endsection
