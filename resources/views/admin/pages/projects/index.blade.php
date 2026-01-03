@extends('admin.layouts.main.app')

@section('content')
    @php
        $mode = $mode ?? 'active'; // active|trash
    @endphp

    <div class="kt-container-fixed"
         data-page="{{ $mode === 'trash' ? 'projects.trash' : 'projects.index' }}"
         data-mode="{{ $mode }}"
         data-perpage="{{ $perPage ?? 25 }}">
        <div class="grid gap-5 lg:gap-7.5">

            @includeIf('admin.partials._flash')

            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex flex-col">
                    <h1 class="text-xl font-semibold">{{ $pageTitle ?? 'Projeler' }}</h1>
                    <div class="text-sm text-muted-foreground">
                        {{ $mode === 'trash' ? 'Çöp Kutusu' : 'Aktif Liste' }}
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    @if($mode !== 'trash')
                        <a href="{{ route('admin.projects.create') }}" class="kt-btn kt-btn-primary">Yeni Proje</a>
                        <a href="{{ route('admin.projects.trash') }}" class="kt-btn kt-btn-light">Çöp</a>
                    @else
                        <a href="{{ route('admin.projects.index') }}" class="kt-btn kt-btn-light">Aktif Liste</a>
                    @endif
                </div>
            </div>

            <div class="kt-card kt-card-grid min-w-full">
                <div class="kt-card-header py-5 flex-wrap gap-4">
                    <div class="flex items-center gap-2">
                        <input id="projectsSearch"
                               class="kt-input w-72"
                               value="{{ $q ?? '' }}"
                               placeholder="Ara: başlık / slug" />
                        <button id="projectsSearchBtn" class="kt-btn kt-btn-light">Ara</button>
                    </div>

                    <div class="ms-auto flex items-center gap-2">
                        <select id="projectsPageSize" class="kt-select w-24"></select>
                    </div>
                </div>

                <div class="kt-card-content">
                    <div class="overflow-x-auto">
                        <table class="kt-table" id="projectsTable">
                            <thead>
                            <tr>
                                <th class="w-[70px]">ID</th>
                                <th>Başlık</th>
                                <th class="w-[160px]">Status</th>
                                <th class="w-[220px] text-right">İşlem</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($projects as $p)
                                <tr data-id="{{ $p->id }}">
                                    <td>{{ $p->id }}</td>

                                    <td class="font-medium">
                                        <div class="flex flex-col">
                                            <a href="{{ route('admin.projects.edit', $p) }}" class="hover:underline">
                                                {{ $p->title }}
                                            </a>
                                            <span class="text-xs text-muted-foreground">{{ $p->slug }}</span>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="kt-badge kt-badge-light">{{ $p->status }}</span>
                                    </td>

                                    <td class="text-right">
                                        <div class="flex justify-end gap-2">
                                            @if($mode !== 'trash')
                                                <a class="kt-btn kt-btn-sm kt-btn-light"
                                                   href="{{ route('admin.projects.edit', $p) }}">
                                                    Düzenle
                                                </a>

                                                <button type="button"
                                                        class="kt-btn kt-btn-sm kt-btn-danger"
                                                        data-action="delete">
                                                    Sil
                                                </button>
                                            @else
                                                <button type="button"
                                                        class="kt-btn kt-btn-sm kt-btn-light"
                                                        data-action="restore">
                                                    Restore
                                                </button>

                                                <button type="button"
                                                        class="kt-btn kt-btn-sm kt-btn-danger"
                                                        data-action="force-delete">
                                                    Force
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted-foreground py-10">
                                        Kayıt bulunamadı.
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
                    <div class="order-2 md:order-1">
                        {{ $projects->firstItem() ?? 0 }}-{{ $projects->lastItem() ?? 0 }} / {{ $projects->total() }}
                    </div>

                    <div class="order-1 md:order-2">
                        {{ $projects->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
