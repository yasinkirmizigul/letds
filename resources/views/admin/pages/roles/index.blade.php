@extends('admin.layouts.main.app')

@section('content')
    @php
        $u = auth()->user();
        $myP = $u?->topRolePriority() ?? 0;
    @endphp
    <div class="kt-container-fixed max-w-[90%]" data-page="roles.index">
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

                        @perm('roles.create')
                        <a href="{{ route('admin.roles.create') }}" class="kt-btn kt-btn-sm kt-btn-primary">
                            Rol Ekle
                        </a>
                        @endperm
                    </div>
                </div>

                <div class="kt-card-content">
                    <div class="grid" id="roles_dt">
                        <div class="kt-scrollable-x-auto overflow-y-hidden">
                            <table class="kt-table table-auto kt-table-border w-full" id="roles_table">
                                <thead>
                                <tr>
                                    <th class="min-w-[260px]">Rol Adı</th>
                                    <th class="w-[120px] text-center">Priority</th>
                                    <th class="min-w-[520px]">Yetkiler</th>
                                    <th class="min-w-[160px]">Oluşturulma</th>
                                    <th class="w-[90px]"></th>
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($roles as $role)
                                    <tr>
                                        <td class="font-medium">{{ $role->name }}</td>

                                        <td class="text-center">
                                            <span class="kt-badge kt-badge-sm kt-badge-light">
                                                {{ (int)($role->priority ?? 0) }}
                                            </span>
                                        </td>

                                        <td class="text-sm text-secondary-foreground">
                                            @php
                                                $perms = $role->permissions;
                                                $shown = $perms->take(6);
                                                $more = $perms->count() - $shown->count();
                                            @endphp

                                            <div class="flex flex-wrap gap-1">
                                                @foreach($shown as $perm)
                                                    <span class="kt-badge kt-badge-sm kt-badge-light">
                                                        {{ $perm->name }}
                                                    </span>
                                                @endforeach

                                                @if($more > 0)
                                                    <span class="kt-badge kt-badge-sm kt-badge-mono">
                                                        +{{ $more }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="text-sm text-secondary-foreground">
                                            {{ $role->created_at->format('d.m.Y') }}
                                        </td>

                                        <td>
                                            <div class="flex items-center justify-end gap-1">
                                                @perm('roles.update')
                                                @if($role->slug !== 'superadmin' && $myP > (int)($role->priority ?? 0))
                                                    <a href="{{ route('admin.roles.edit', $role) }}"
                                                       class="kt-btn kt-btn-sm kt-btn-icon kt-btn-warning"
                                                       title="Düzenle">
                                                        <i class="ki-filled ki-notepad-edit"></i>
                                                    </a>
                                                @endif
                                                @endperm
                                                @perm('roles.delete')
                                                @if($role->slug !== 'superadmin' && $myP > (int)($role->priority ?? 0))
                                                    <button type="button"
                                                            class="kt-btn kt-btn-sm kt-btn-icon kt-btn-destructive"
                                                            title="Sil"
                                                            data-kt-modal-toggle="#roleDeleteModal"
                                                            data-role-id="{{ $role->id }}"
                                                            data-role-name="{{ $role->name }}"
                                                            data-role-users="{{ (int) $role->users_count }}">
                                                        <i class="ki-filled ki-trash"></i>
                                                    </button>

                                                    <form id="role_delete_form_{{ $role->id }}"
                                                          action="{{ route('admin.roles.destroy', $role) }}"
                                                          method="POST"
                                                          class="hidden">
                                                        @csrf
                                                        @method('DELETE')
                                                    </form>
                                                @endif
                                                @endperm
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>

                            <template id="dt-empty-roles">
                                <tr data-kt-empty-row="true">
                                    <td colspan="5" class="py-12">
                                        <div class="flex flex-col items-center justify-center gap-2 text-center text-muted-foreground">
                                            <i class="ki-outline ki-folder-open text-4xl mb-2"></i>
                                            <div class="font-medium text-secondary-foreground">
                                                Henüz kayıt bulunmuyor.
                                            </div>
                                            <div class="text-sm">
                                                Yeni rol ekleyerek başlayabilirsiniz.
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <template id="dt-zero-roles">
                                <tr data-kt-zero-row="true">
                                    <td colspan="5" class="py-12">
                                        <div class="flex flex-col items-center justify-center gap-2 text-center text-muted-foreground">
                                            <i class="ki-outline ki-search-list text-4xl mb-2"></i>
                                            <div class="font-medium text-secondary-foreground">
                                                Sonuç bulunamadı.
                                            </div>
                                            <div class="text-sm">
                                                Arama kriterlerini değiştirip tekrar deneyin.
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </div>

                        <div class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
                            <div class="flex items-center gap-2 order-2 md:order-1">
                                Göster
                                <select class="kt-select w-16" id="rolesPageSize" name="perpage" data-kt-select="true"></select>
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

        {{-- Delete Confirm Modal --}}
        @perm('roles.delete')
        <div class="kt-modal" id="roleDeleteModal" data-kt-modal="true">
            <div class="kt-modal-dialog max-w-lg">
                <div class="kt-modal-content">
                    <div class="kt-modal-header">
                        <h3 class="kt-modal-title">Rolü Sil</h3>
                        <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" data-kt-modal-dismiss="true">
                            <i class="ki-outline ki-cross"></i>
                        </button>
                    </div>

                    <div class="kt-modal-body p-6">
                        <div class="flex items-start gap-3" id="roleDeleteAlert">
                            <i class="ki-filled ki-warning-2 text-2xl text-danger"></i>
                            <div class="grid gap-1">
                                <div class="font-semibold text-foreground">
                                    Bu rol silinecek: <span id="roleDeleteName" class="font-bold"></span>
                                </div>
                                <div class="text-sm text-muted-foreground" id="roleDeleteUsersWrap">
                                    Etkilenecek kullanıcı: <span id="roleDeleteUsers" class="font-semibold text-foreground">0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="kt-modal-footer justify-end gap-2">
                        <button class="kt-btn kt-btn-light" type="button" data-kt-modal-dismiss="true">Vazgeç</button>
                        <button class="kt-btn kt-btn-danger" type="button" id="roleDeleteConfirmBtn">
                            Sil
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endperm
    </div>
@endsection
