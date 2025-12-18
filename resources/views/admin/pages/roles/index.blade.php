@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed">
        @include('admin.partials._flash')

        <div class="flex items-center justify-between mb-4">
            <h1 class="text-lg font-semibold">Roller</h1>
        </div>

        <div class="grid gap-5 lg:gap-7.5">
            <div class="kt-card kt-card-grid min-w-full">
                <div class="kt-card-header py-5 flex-wrap gap-4">
                    <h3 class="kt-card-title">Roller</h3>

                    <div class="flex items-center gap-2">
                        <input
                            id="rolesSearch"
                            type="text"
                            class="kt-input kt-input-sm"
                            placeholder="Rol / yetki ara..."
                        />

                        @if(auth()->user()->hasPermission('roles.create'))
                            <a href="{{ route('admin.roles.create') }}" class="kt-btn kt-btn-sm kt-btn-primary">
                                Rol Ekle
                            </a>
                        @endif
                    </div>
                </div>

                <div class="kt-card-content">
                    <div class="grid" id="roles_dt">
                        <div class="kt-scrollable-x-auto">
                            <table class="kt-table table-auto kt-table-border w-full" id="roles_table">
                                <thead>
                                <tr>
                                    <th class="min-w-[260px]">Rol Adı</th>
                                    <th class="min-w-[520px]">Yetkiler</th>
                                    <th class="min-w-[160px]">Oluşturulma</th>
                                    <th class="w-[60px]"></th>
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($roles as $role)
                                    <tr>
                                        <td class="font-medium">{{ $role->name }}</td>
                                        <td class="text-sm text-secondary-foreground">
                                            {{ $role->permissions->pluck('name')->join(', ') }}
                                        </td>
                                        <td class="text-sm text-secondary-foreground">
                                            {{ $role->created_at->format('d.m.Y') }}
                                        </td>
                                        <td>
                                            @if(auth()->user()->hasPermission('roles.update'))
                                                <a href="{{ route('admin.roles.edit', $role) }}"
                                                   class="kt-btn kt-btn-sm kt-btn-icon kt-btn-mono">
                                                    <i class="ki-filled ki-notepad-edit"></i>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>

                            {{-- Empty UI template (tablo tamamen boşsa) --}}
                            <template id="dt-empty-roles">
                                <div class="flex flex-col items-center justify-center gap-2 text-center py-12 text-muted-foreground">
                                    <i class="ki-outline ki-folder-open text-4xl mb-2"></i>
                                    <div class="font-medium text-secondary-foreground">Henüz kayıt bulunmuyor.</div>
                                    <div class="text-sm">Yeni rol ekleyerek başlayabilirsiniz.</div>
                                </div>
                            </template>

                            {{-- Zero UI (arama var ama sonuç yoksa) --}}
                            <template id="dt-zero-roles">
                                <div class="flex flex-col items-center justify-center gap-2 text-center py-12 text-muted-foreground">
                                    <i class="ki-outline ki-search-list text-4xl mb-2"></i>
                                    <div class="font-medium text-secondary-foreground">Sonuç bulunamadı.</div>
                                    <div class="text-sm">Arama kriterlerini değiştirip tekrar deneyin.</div>
                                </div>
                            </template>
                        </div>

                        <div class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
                            <div class="flex items-center gap-2 order-2 md:order-1">
                                Göster
                                <select class="kt-select w-16" id="rolesPageSize" name="perpage"></select>
                                / sayfa
                            </div>

                            <div class="flex items-center gap-4 order-1 md:order-2">
                                <span id="rolesInfo"></span>
                                <div class="kt-datatable-pagination" id="rolesPagination"></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('page_js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            initDataTable({
                table: '#roles_table',
                search: '#rolesSearch',
                pageSize: '#rolesPageSize',
                info: '#rolesInfo',
                pagination: '#rolesPagination',

                pageLength: 10,
                lengthMenu: [5, 10, 25, 50],
                order: [[0, 'asc']],
                dom: 't',

                emptyTemplate: '#dt-empty-roles',
                zeroTemplate: '#dt-zero-roles',

                columnDefs: [
                    { orderable: false, targets: [3] },
                    { searchable: false, targets: [3] },
                    { className: 'text-center', targets: [2] },
                ],
            });
        });
    </script>
@endpush
