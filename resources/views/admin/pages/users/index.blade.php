@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[90%]"
         data-page="users.index"
         data-current-user-id="{{ auth()->id() }}">

        @include('admin.partials._flash')

        <div class="grid gap-5 lg:gap-7.5">

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div class="kt-card">
                    <div class="kt-card-content p-5">
                        <div class="text-sm text-muted-foreground">Toplam kullanici</div>
                        <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($stats['total'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="kt-card">
                    <div class="kt-card-content p-5">
                        <div class="text-sm text-muted-foreground">Aktif</div>
                        <div class="mt-2 text-2xl font-semibold text-success">{{ number_format((int) ($stats['active'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="kt-card">
                    <div class="kt-card-content p-5">
                        <div class="text-sm text-muted-foreground">Pasif</div>
                        <div class="mt-2 text-2xl font-semibold text-danger">{{ number_format((int) ($stats['inactive'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="kt-card">
                    <div class="kt-card-content p-5">
                        <div class="text-sm text-muted-foreground">Admin / Super Admin</div>
                        <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($stats['admins'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="kt-card">
                    <div class="kt-card-content p-5">
                        <div class="text-sm text-muted-foreground">Super Admin</div>
                        <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($stats['superadmins'] ?? 0)) }}</div>
                    </div>
                </div>
            </div>

            <div class="kt-card kt-card-grid min-w-full">
                <div class="kt-card-header py-5 flex-wrap gap-4">
                    <div class="flex flex-col">
                        <h3 class="kt-card-title">Kullanici Yonetimi</h3>
                        <div class="text-sm text-muted-foreground">Rol, durum ve erisim akislarini tek listede yonet.</div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <input
                            id="usersSearch"
                            type="text"
                            class="kt-input kt-input-sm w-[240px]"
                            placeholder="Ad, e-posta veya rol ara..." />

                        <select id="usersRoleFilter" class="kt-select kt-select-sm w-[180px]" data-kt-select="true">
                            <option value="all">Tum roller</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->slug }}">{{ $role->name }}</option>
                            @endforeach
                        </select>

                        <select id="usersStatusFilter" class="kt-select kt-select-sm w-[160px]" data-kt-select="true">
                            <option value="all">Tum durumlar</option>
                            <option value="active">Aktif</option>
                            <option value="inactive">Pasif</option>
                        </select>

                        <button type="button" id="usersClearFiltersBtn" class="kt-btn kt-btn-sm kt-btn-light">
                            Temizle
                        </button>

                        @perm('users.create')
                            <a href="{{ route('admin.users.create') }}" class="kt-btn kt-btn-sm kt-btn-primary">
                                Kullanici Ekle
                            </a>
                        @endperm
                    </div>
                </div>

                <div class="kt-card-content">
                    <div class="grid" id="users_dt">

                        <div class="kt-scrollable-x-auto overflow-y-hidden">
                            <table class="kt-table table-auto kt-table-border w-full" id="users_table">
                                <thead>
                                <tr>
                                    <th class="w-[55px]">
                                        <input class="kt-checkbox kt-checkbox-sm flex" id="users_check_all" type="checkbox" />
                                    </th>
                                    <th class="min-w-[260px]">Kullanici</th>
                                    <th class="min-w-[220px]">E-posta</th>
                                    <th class="min-w-[240px]">Roller</th>
                                    <th class="min-w-[140px]">Durum</th>
                                    <th class="min-w-[160px]">Olusturulma</th>
                                    <th class="w-[70px]"></th>
                                    <th class="w-[90px]"></th>
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($users as $user)
                                    @php
                                        $roleSlugs = $user->roles->pluck('slug')->filter()->values();
                                        $roleNames = $user->roles->pluck('name')->filter()->values();
                                        $topRole = $user->roles->sortByDesc('priority')->first();
                                        $isCurrentUser = auth()->id() === $user->id;
                                    @endphp
                                    <tr
                                        data-row-id="{{ $user->id }}"
                                        data-status="{{ $user->is_active ? 'active' : 'inactive' }}"
                                        data-role-slugs="|{{ $roleSlugs->implode('|') }}|">
                                        <td>
                                            <input class="kt-checkbox kt-checkbox-sm users_row_check"
                                                   type="checkbox"
                                                   value="{{ $user->id }}" />
                                        </td>

                                        <td>
                                            <div class="flex flex-col gap-1">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span class="font-medium text-sm">{{ $user->name }}</span>
                                                    <span class="kt-badge kt-badge-outline">#{{ $user->id }}</span>
                                                    @if($topRole)
                                                        <span class="kt-badge kt-badge-light">{{ $topRole->name }}</span>
                                                    @endif
                                                    @if($isCurrentUser)
                                                        <span class="kt-badge kt-badge-light-info">Bu hesap</span>
                                                    @endif
                                                </div>
                                                <span class="text-sm text-secondary-foreground">
                                                    {{ $user->is_active ? 'Panele erisebilir.' : 'Hesap pasif durumda.' }}
                                                </span>
                                            </div>
                                        </td>

                                        <td class="text-sm text-foreground font-normal">{{ $user->email }}</td>

                                        <td class="text-sm text-secondary-foreground font-normal">
                                            @if($roleNames->isNotEmpty())
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach($roleNames as $name)
                                                        <span class="kt-badge kt-badge-light">{{ $name }}</span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-muted-foreground">Rol atanmis degil</span>
                                            @endif
                                        </td>

                                        <td>
                                            @if($user->is_active)
                                                <span class="kt-badge kt-badge-success">Aktif</span>
                                            @else
                                                <span class="kt-badge kt-badge-danger">Pasif</span>
                                            @endif
                                        </td>

                                        <td class="text-sm text-secondary-foreground font-normal">
                                            {{ $user->created_at?->format('d.m.Y') }}
                                        </td>

                                        <td>
                                            @perm('users.update')
                                                <a href="{{ route('admin.users.edit', $user) }}"
                                                   class="kt-btn kt-btn-sm kt-btn-icon kt-btn-warning">
                                                    <i class="ki-filled ki-notepad-edit"></i>
                                                </a>
                                            @endperm
                                        </td>

                                        <td>
                                            @perm('users.delete')
                                                @if($isCurrentUser)
                                                    <button type="button"
                                                            class="kt-btn kt-btn-sm kt-btn-light"
                                                            disabled
                                                            title="Kendi hesabini silemezsin">
                                                        Korumali
                                                    </button>
                                                @else
                                                    <form method="POST"
                                                          data-confirm="delete-user"
                                                          action="{{ route('admin.users.destroy', $user) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-destructive">
                                                            <i class="ki-filled ki-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            @endperm
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>

                            <template id="dt-empty-users">
                                <tr data-kt-empty-row="true">
                                    <td colspan="8" class="py-12">
                                        <div class="flex flex-col items-center justify-center gap-2 text-center text-muted-foreground">
                                            <i class="ki-outline ki-folder-open text-4xl mb-2"></i>
                                            <div class="font-medium text-secondary-foreground">Henuz kayit bulunmuyor.</div>
                                            <div class="text-sm">Yeni kayit ekleyerek baslayabilirsiniz.</div>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <template id="dt-zero-users">
                                <tr data-kt-zero-row="true">
                                    <td colspan="8" class="py-12">
                                        <div class="flex flex-col items-center justify-center gap-2 text-center text-muted-foreground">
                                            <i class="ki-outline ki-search-list text-4xl mb-2"></i>
                                            <div class="font-medium text-secondary-foreground">Sonuc bulunamadi.</div>
                                            <div class="text-sm">Arama ve filtreleri degistirip tekrar dene.</div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </div>

                        <div class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
                            <div class="flex items-center gap-2 order-2 md:order-1">
                                Goster
                                <select class="kt-select w-16" id="usersPageSize" data-kt-select="true"></select>
                                / sayfa
                            </div>

                            <div class="flex items-center gap-4 order-1 md:order-2">
                                <span id="usersInfo"></span>
                                <div class="kt-datatable-pagination" id="usersPagination"></div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
