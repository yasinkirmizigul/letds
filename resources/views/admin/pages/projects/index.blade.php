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
        data-page="projects.index"
        data-mode="{{ $mode }}"
        data-perpage="{{ $perPage ?? 25 }}"
        data-status-options='@json($statusOptions ?? [])'
        data-public-statuses='@json(array_values($publicStatuses ?? []))'
        data-bulk-delete-url="{{ route('admin.projects.bulkDestroy') }}"
        data-bulk-restore-url="{{ route('admin.projects.bulkRestore') }}"
        data-bulk-force-delete-url="{{ route('admin.projects.bulkForceDestroy') }}"
    >
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-xl font-semibold">
                    {{ $isTrash ? 'Projeler Cop Kutusu' : 'Proje Yonetimi' }}
                </h1>
                <div class="text-sm text-muted-foreground">
                    {{ $isTrash ? 'Silinen projeleri geri yukleyebilir veya kalici olarak silebilirsiniz.' : 'Workflow, SEO, vitrin ve kategori akislarini tek ekrandan yonetin.' }}
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('admin.projects.index') }}"
                    class="kt-btn kt-btn-sm {{ $isTrash ? 'kt-btn-light' : 'kt-btn-primary' }}"
                >
                    Aktif Kayitlar
                </a>
                <a
                    href="{{ route('admin.projects.trash') }}"
                    class="kt-btn kt-btn-sm {{ $isTrash ? 'kt-btn-primary' : 'kt-btn-light' }}"
                >
                    Cop Kutusu
                </a>

                @perm('projects.create')
                    <a href="{{ route('admin.projects.create') }}" class="kt-btn kt-btn-sm kt-btn-primary">
                        Yeni Proje
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
                <div class="text-sm text-muted-foreground">Public Hazir</div>
                <div class="mt-2 text-3xl font-semibold text-success">{{ $stats['public'] ?? 0 }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Anasayfada</div>
                <div class="mt-2 text-3xl font-semibold text-primary">{{ $stats['featured'] ?? 0 }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Workflow Akisi</div>
                <div class="mt-2 text-3xl font-semibold text-warning">{{ $stats['workflow'] ?? 0 }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Copte</div>
                <div class="mt-2 text-3xl font-semibold text-danger">{{ $stats['trash'] ?? 0 }}</div>
            </div>
        </div>

        <div class="kt-card kt-card-grid min-w-full">
            <div class="kt-card-header py-5 flex-wrap gap-4">
                <div>
                    <h3 class="kt-card-title">{{ $isTrash ? 'Silinen Projeler' : 'Proje Listesi' }}</h3>
                    <div class="text-sm text-muted-foreground">
                        Status, public gorunurluk, vitrin secimi ve kategori dagilimini tek satirda inceleyin.
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <input
                        id="projectsSearch"
                        type="text"
                        class="kt-input kt-input-sm w-full md:w-[260px]"
                        placeholder="Baslik, slug, icerik ara..."
                        value="{{ $q }}"
                    />

                    <select
                        id="projectsStatusFilter"
                        class="kt-select w-full md:w-[220px]"
                        data-kt-select="true"
                        data-kt-select-placeholder="Durum"
                    >
                        <option value="all" @selected($status === 'all')>Tum durumlar</option>
                        @foreach(($statusOptions ?? []) as $key => $option)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $option['label'] }}</option>
                        @endforeach
                    </select>

                    <select
                        id="projectsCategoryFilter"
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

                    <button type="button" id="projectsClearFiltersBtn" class="kt-btn kt-btn-sm kt-btn-light">
                        Filtreleri Temizle
                    </button>
                </div>
            </div>

            <div class="kt-card-content">
                <div id="projectsBulkBar" class="hidden kt-card mb-4 border border-border">
                    <div class="kt-card-content p-3 flex items-center justify-between gap-3">
                        <div class="text-sm text-muted-foreground">
                            Secili: <b id="projectsSelectedCount">0</b>
                        </div>

                        <div class="flex items-center gap-2">
                            @if($isTrash)
                                @perm('projects.restore')
                                    <button type="button" class="kt-btn kt-btn-sm kt-btn-success" id="projectsBulkRestoreBtn" disabled>
                                        Geri Yukle
                                    </button>
                                @endperm
                                @perm('projects.force_delete')
                                    <button type="button" class="kt-btn kt-btn-sm kt-btn-destructive" id="projectsBulkForceDeleteBtn" disabled>
                                        Kalici Sil
                                    </button>
                                @endperm
                            @else
                                @perm('projects.delete')
                                    <button type="button" class="kt-btn kt-btn-sm kt-btn-destructive" id="projectsBulkDeleteBtn" disabled>
                                        Sil
                                    </button>
                                @endperm
                            @endif
                        </div>
                    </div>
                </div>

                <div class="grid" id="projects_dt">
                    <div class="kt-scrollable-x-auto overflow-y-hidden">
                        <table id="projects_table" class="kt-table table-auto kt-table-border w-full">
                            <thead>
                            <tr>
                                <th class="w-[55px] dt-orderable-none">
                                    <input class="kt-checkbox kt-checkbox-sm" id="projects_check_all" type="checkbox">
                                </th>
                                <th class="min-w-[360px]">Proje</th>
                                <th class="min-w-[240px]">Workflow</th>
                                <th class="min-w-[240px]">Gorunurluk ve Vitrin</th>
                                <th class="min-w-[180px]">Son Guncelleme</th>
                                <th class="w-[64px]"></th>
                                <th class="w-[80px]"></th>
                            </tr>
                            </thead>

                            <tbody>
                            @foreach($projects as $project)
                                @php
                                    $img = $project->featuredMediaUrl() ?: $project->featured_image_url;
                                    $seoScore = $project->seoCompletenessScore();
                                    $readTime = $project->estimatedReadTimeMinutes();
                                    $isPublic = \App\Models\Admin\Project\Project::statusIsPublic($project->status);
                                    $categoryIdsAttr = '|' . $project->categories->pluck('id')->map(fn ($id) => (int) $id)->implode('|') . '|';
                                @endphp

                                <tr
                                    data-id="{{ $project->id }}"
                                    data-status="{{ $project->status }}"
                                    data-featured="{{ $project->is_featured ? '1' : '0' }}"
                                    data-public="{{ $isPublic ? '1' : '0' }}"
                                    data-category-ids="{{ $categoryIdsAttr }}"
                                >
                                    <td class="w-[55px]">
                                        <input class="kt-checkbox kt-checkbox-sm projects-check" type="checkbox" value="{{ $project->id }}">
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
                                                        <a class="font-semibold text-foreground hover:text-primary" href="{{ route('admin.projects.edit', $project->id) }}">
                                                            {{ $project->title }}
                                                        </a>
                                                    @else
                                                        <span class="font-semibold text-foreground">{{ $project->title }}</span>
                                                    @endif
                                                    <span class="kt-badge kt-badge-sm kt-badge-light">#{{ $project->id }}</span>
                                                    <span class="kt-badge kt-badge-sm {{ $seoScore >= 80 ? 'kt-badge-light-success' : ($seoScore >= 50 ? 'kt-badge-light-warning' : 'kt-badge-light-danger') }}">
                                                        SEO %{{ $seoScore }}
                                                    </span>
                                                    <span class="kt-badge kt-badge-sm kt-badge-light">
                                                        {{ $readTime > 0 ? $readTime . ' dk okuma' : 'Kisa yazi' }}
                                                    </span>
                                                </div>

                                                <div class="text-sm text-muted-foreground break-all">
                                                    /projects/{{ $project->slug }}
                                                </div>

                                                <div class="text-sm text-muted-foreground leading-6">
                                                    {{ $project->excerptPreview(130) ?: 'Icerik ozeti bulunmuyor.' }}
                                                </div>

                                                @if($project->categories->isNotEmpty())
                                                    <div class="flex flex-wrap items-center gap-1">
                                                        @foreach($project->categories as $category)
                                                            <span class="kt-badge kt-badge-sm kt-badge-light">{{ $category->name }}</span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="grid gap-2">
                                            <button
                                                type="button"
                                                class="{{ \App\Models\Admin\Project\Project::statusBadgeClass($project->status) }} js-status-trigger"
                                                data-status="{{ $project->status }}"
                                                data-status-url="{{ route('admin.projects.status', $project) }}"
                                            >
                                                {{ \App\Models\Admin\Project\Project::statusLabel($project->status) }}
                                                <i class="ki-outline ki-down ml-1"></i>
                                            </button>
                                            <div class="text-xs text-muted-foreground js-public-hint">
                                                {{ $isPublic ? 'Bu statu site tarafinda gorunebilir.' : 'Bu statu admin ici workflow asamasinda kalir.' }}
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="grid gap-3">
                                            <div class="flex items-center justify-between gap-3">
                                                <span class="js-public-badge kt-badge kt-badge-sm {{ $isPublic ? 'kt-badge-light-success' : 'kt-badge-light text-muted-foreground' }}">
                                                    {{ $isPublic ? 'Sitede gorunebilir' : 'Sitede gizli' }}
                                                </span>
                                                @if(!$isTrash)
                                                    @perm('projects.update')
                                                        <label class="kt-switch kt-switch-sm">
                                                            <input
                                                                type="checkbox"
                                                                class="js-featured-toggle kt-switch kt-switch-mono"
                                                                data-url="{{ route('admin.projects.featured', $project) }}"
                                                                @checked($project->is_featured)
                                                            >
                                                        </label>
                                                    @endperm
                                                @endif
                                            </div>

                                            <div class="js-featured-badge-wrap text-xs text-muted-foreground">
                                                <span class="js-featured-badge kt-badge kt-badge-sm {{ $project->is_featured ? 'kt-badge-light-success' : 'kt-badge-light text-muted-foreground' }}">
                                                    {{ $project->is_featured ? 'Anasayfada' : 'Kapali' }}
                                                </span>
                                                <span class="block mt-1 js-featured-at">
                                                    {{ $project->featured_at ? 'Secim: ' . $project->featured_at->format('d.m.Y H:i') : 'Secim yapilmamis' }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <td data-order="{{ $project->updated_at?->timestamp ?? 0 }}">
                                        <div class="grid gap-1 text-sm">
                                            <span class="font-medium text-foreground">{{ $project->updated_at?->format('d.m.Y H:i') ?: '-' }}</span>
                                            <span class="text-muted-foreground">{{ $project->created_at?->format('d.m.Y H:i') ?: '-' }} olusturuldu</span>
                                        </div>
                                    </td>

                                    <td class="text-end">
                                        @if(!$isTrash)
                                            @perm('projects.update')
                                                <a class="kt-btn kt-btn-sm kt-btn-icon kt-btn-warning" href="{{ route('admin.projects.edit', $project->id) }}" title="Duzenle">
                                                    <i class="ki-filled ki-notepad-edit"></i>
                                                </a>
                                            @endperm
                                        @endif
                                    </td>

                                    <td class="text-end">
                                        <div class="flex justify-end gap-1">
                                            @if($isTrash)
                                                @perm('projects.restore')
                                                    <button
                                                        type="button"
                                                        class="kt-btn kt-btn-sm kt-btn-success"
                                                        data-action="restore"
                                                        data-url="{{ route('admin.projects.restore', $project->id) }}"
                                                    >
                                                        <i class="ki-outline ki-arrow-circle-left"></i>
                                                    </button>
                                                @endperm

                                                @perm('projects.force_delete')
                                                    <button
                                                        type="button"
                                                        class="kt-btn kt-btn-sm kt-btn-destructive"
                                                        data-action="force-delete"
                                                        data-url="{{ route('admin.projects.forceDestroy', $project->id) }}"
                                                    >
                                                        <i class="ki-outline ki-trash"></i>
                                                    </button>
                                                @endperm
                                            @else
                                                @perm('projects.delete')
                                                    <button
                                                        type="button"
                                                        class="kt-btn kt-btn-sm kt-btn-destructive"
                                                        data-action="delete"
                                                        data-url="{{ route('admin.projects.destroy', $project) }}"
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

                    <template id="dt-empty-projects">
                        <tr data-kt-empty-row="true">
                            <td colspan="7" class="py-12">
                                <div class="flex flex-col items-center text-center gap-2">
                                    <i class="ki-outline ki-folder text-3xl text-muted-foreground"></i>
                                    <div class="font-semibold">Henuz proje bulunmuyor.</div>
                                    <div class="text-sm text-muted-foreground">Yeni bir proje olusturarak baslayabilirsiniz.</div>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template id="dt-zero-projects">
                        <tr data-kt-zero-row="true">
                            <td colspan="7" class="py-12">
                                <div class="flex flex-col items-center text-center gap-2">
                                    <i class="ki-outline ki-magnifier text-3xl text-muted-foreground"></i>
                                    <div class="font-semibold">Filtreye uygun proje bulunamadi.</div>
                                    <div class="text-sm text-muted-foreground">Arama ve filtre secimlerini degistirip tekrar deneyin.</div>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <div class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
                        <div class="flex items-center gap-2 order-2 md:order-1">
                            Goster
                            <select class="kt-select w-16" id="projectsPageSize" data-kt-select="true"></select>
                            / sayfa
                        </div>

                        <div class="flex items-center gap-4 order-1 md:order-2">
                            <span id="projectsInfo"></span>
                            <div class="kt-datatable-pagination" id="projectsPagination"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
