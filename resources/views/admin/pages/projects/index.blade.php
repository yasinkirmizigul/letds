{{-- resources/views/admin/pages/projects/index.blade.php --}}
@extends('admin.layouts.main.app')

@section('content')
    @php
        $mode = request()->routeIs('admin.projects.trash') ? 'trash' : 'active';
        $per  = (int) request('perpage', 25);
    @endphp

    <div
        data-page="{{ $mode === 'trash' ? 'projects.trash' : 'projects.index' }}"
        data-perpage="{{ $per }}"
        class="grid gap-5 lg:gap-7.5"
    >
        {{-- Bulk bar (BLOG İLE AYNI: ayrı kt-card) --}}
        <div id="projectsBulkBar" class="hidden kt-card mb-4">
            <div class="kt-card-content p-3 flex items-center justify-between gap-3">
                <div class="text-sm text-muted-foreground">
                    Seçili: <b id="projectsSelectedCount">0</b>
                </div>

                <div class="flex items-center gap-2">
                    @if($mode === 'trash')
                        <button type="button" id="projectsBulkRestoreBtn" class="kt-btn kt-btn-sm kt-btn-light"
                                disabled>
                            <i class="ki-outline ki-arrows-circle"></i> Geri Yükle
                        </button>
                        <button type="button" id="projectsBulkForceDeleteBtn" class="kt-btn kt-btn-sm kt-btn-danger"
                                disabled>
                            <i class="ki-outline ki-trash"></i> Kalıcı Sil
                        </button>
                    @else
                        <button type="button" id="projectsBulkDeleteBtn" class="kt-btn kt-btn-sm kt-btn-danger"
                                disabled>
                            <i class="ki-outline ki-trash"></i> Sil
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- List card (BLOG İLE AYNI) --}}
        <div class="kt-card kt-card-grid">
            <div class="kt-card-header py-5 flex-wrap gap-4">
                <div class="kt-card-title flex flex-wrap items-center gap-3">
                    <h3 class="text-base font-semibold">
                        {{ $mode === 'trash' ? 'Projeler (Çöp)' : 'Projeler' }}
                    </h3>

                    <div class="relative">
                        <input
                            id="projectsSearch"
                            type="text"
                            class="kt-input kt-input-sm w-64"
                            placeholder="Ara..."
                            value="{{ request('q') }}"
                        >
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-sm text-muted-foreground">Sayfa başı</span>
                        <select
                            id="projectsPageSize"
                            class="kt-select kt-select-sm w-20"
                            data-kt-select="true"
                        ></select>
                    </div>
                </div>

                <div class="kt-card-toolbar flex flex-wrap items-center gap-2">
                    @if($mode === 'trash')
                        <a href="{{ route('admin.projects.index') }}" class="kt-btn kt-btn-light kt-btn-sm">
                            <i class="ki-outline ki-arrow-left"></i> Aktif
                        </a>
                    @else
                        <a href="{{ route('admin.projects.trash') }}" class="kt-btn kt-btn-light kt-btn-sm">
                            <i class="ki-outline ki-trash"></i> Çöp
                        </a>

                        <a href="{{ route('admin.projects.create') }}" class="kt-btn kt-btn-primary kt-btn-sm">
                            <i class="ki-outline ki-plus"></i> Yeni Proje
                        </a>
                    @endif
                </div>
            </div>

            <div class="kt-card-content p-0">
                <div class="kt-scrollable-x-auto overflow-y-hidden">
                    <table id="projects_table" class="kt-table table-auto kt-table-border w-full">
                        <thead>
                        <tr class="text-xs text-muted-foreground">
                            <th class="w-[55px] dt-orderable-none">
                                <input class="kt-checkbox kt-checkbox-sm" id="projects_check_all" type="checkbox">
                            </th>
                            <th class="min-w-[360px]">Proje</th>
                            <th class="min-w-[280px]">Kısa Bağlantı</th>
                            <th class="min-w-[220px]">Durum</th>
                            <th class="w-[200px] text-center">Anasayfa</th>
                            <th class="min-w-[190px] text-right">Tarih</th>
                            <th class="min-w-[120px] text-right">İşlemler</th>
                        </tr>
                        </thead>

                        <tbody>
                        @foreach($projects as $p)
                            @php
                                // featured preview: Media pivot -> fallback legacy
                                $img = $p->featuredMediaUrl() ?: ($p->featured_image_path ? asset('storage/'.$p->featured_image_path) : null);
                            @endphp

                            <tr data-id="{{ $p->id }}">
                                <td class="w-[55px]">
                                    <input class="kt-checkbox kt-checkbox-sm projects-check" type="checkbox"
                                           value="{{ $p->id }}">
                                </td>

                                <td>
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-11 h-11 rounded-xl overflow-hidden border border-border bg-muted/30 shrink-0">
                                            @if($img)
                                                <a href="javascript:void(0)"
                                                   class="block w-full h-full js-img-popover"
                                                   data-popover-img="{{ $img }}">
                                                    <img src="{{ $img }}" class="w-full h-full object-cover" alt="">
                                                </a>
                                            @else
                                                <div
                                                    class="w-full h-full grid place-items-center text-muted-foreground">
                                                    <i class="ki-outline ki-picture text-xl"></i>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="grid">
                                            <a class="font-semibold hover:underline"
                                               href="{{ route('admin.projects.edit', $p->id) }}">
                                                {{ $p->title }}
                                            </a>
                                            <div class="text-xs text-muted-foreground">#{{ $p->id }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td class="text-sm text-muted-foreground">
                                    {{ $p->slug }}
                                </td>

                                <td>
                                    @php($st = $p->status ?? \App\Models\Admin\Project\Project::STATUS_APPOINTMENT_PENDING)

                                    <button type="button"
                                            class="{{ \App\Models\Admin\Project\Project::statusBadgeClass($st) }} js-status-trigger"
                                            data-project-id="{{ $p->id }}"
                                            data-status="{{ $st }}">
                                        {{ \App\Models\Admin\Project\Project::statusLabel($st) }}
                                        <i class="ki-outline ki-down ml-1"></i>
                                    </button>
                                </td>

                                <td class="text-center">
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            class="kt-switch js-featured-toggle"
                                            name="is_published"
                                            data-project-id="{{ $p->id }}"
                                            value="1"
                                            {{ $p->is_featured ? 'checked' : '' }}
                                        >

                                        <span
                                            class="js-featured-badge kt-badge kt-badge-sm kt-badge-light-success transition-opacity duration-200 ease-out {{ $p->is_featured ? 'opacity-100' : 'opacity-0' }}"
                                            {{ $p->is_featured ? '' : 'hidden' }}
                                        >
                                            <i class="ki-outline ki-check-circle mr-1"></i> Anasayfada
                                        </span>
                                    </div>
                                </td>

                                <td class="text-right text-sm text-muted-foreground">
                                    {{ $p->updated_at?->format('d.m.Y H:i') }}
                                </td>

                                <td class="text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <a class="kt-btn kt-btn-sm kt-btn-light"
                                           href="{{ route('admin.projects.edit', $p->id) }}">
                                            <i class="ki-outline ki-pencil"></i>
                                        </a>

                                        @if($mode === 'trash')
                                            <button type="button" class="kt-btn kt-btn-sm kt-btn-light"
                                                    data-action="restore" data-id="{{ $p->id }}">
                                                <i class="ki-outline ki-arrows-circle"></i>
                                            </button>

                                            <button type="button" class="kt-btn kt-btn-sm kt-btn-danger"
                                                    data-action="force-delete" data-id="{{ $p->id }}">
                                                <i class="ki-outline ki-trash"></i>
                                            </button>
                                        @else
                                            <button type="button" class="kt-btn kt-btn-sm kt-btn-danger"
                                                    data-action="delete" data-id="{{ $p->id }}">
                                                <i class="ki-outline ki-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- empty/zero templates (aynı kalır) --}}
                <template id="dt-empty-projects">
                    <div class="p-10 grid place-items-center text-muted-foreground gap-3">
                        <i class="ki-outline ki-folder text-3xl"></i>
                        <div class="font-medium">Kayıt yok</div>
                    </div>
                </template>

                <template id="dt-zero-projects">
                    <div class="p-10 grid place-items-center text-muted-foreground gap-3">
                        <i class="ki-outline ki-magnifier text-3xl"></i>
                        <div class="font-medium">Sonuç bulunamadı</div>
                    </div>
                </template>

                <div class="px-6 py-4 flex items-center justify-between gap-3 border-t border-border">
                    <div id="projectsInfo" class="text-xs text-muted-foreground"></div>
                    <div id="projectsPagination" class="flex items-center justify-end gap-2"></div>
                </div>
            </div>
        </div>
    </div>
@endsection
