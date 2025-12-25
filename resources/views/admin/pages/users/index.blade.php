@extends('admin.layouts.main.app')
@section('content')
    <div class="kt-container-fixed max-w-[90%]" data-page="users.index">

        @include('admin.partials._flash')

        <div class="flex items-center justify-between mb-4">
            <h1 class="text-lg font-semibold">Kullanıcı Yönetimi</h1>
        </div>

        <div class="grid gap-5 lg:gap-7.5">
            <div class="kt-card kt-card-grid min-w-full">
                <div class="kt-card-header py-5 flex-wrap gap-4">
                    <h3 class="kt-card-title">Kullanıcılar</h3>

                    <div class="flex items-center gap-2">
                        <input
                            id="usersSearch"
                            type="text"
                            class="kt-input kt-input-sm"
                            placeholder="Ad / e-posta / rol ara..."
                        />

                        @perm('users.create')
                            <a href="{{ route('admin.users.create') }}" class="kt-btn kt-btn-sm kt-btn-primary">
                                Kullanıcı Ekle
                            </a>
                        @endperm
                    </div>
                </div>

                <div class="kt-card-content">
                    <div class="grid" id="users_dt">

                        <div class="overflow-hidden">
                            <table class="kt-table table-auto kt-table-border w-full" id="users_table">
                                <thead>
                                <tr>
                                    <th class="w-[55px]">
                                        <input class="kt-checkbox kt-checkbox-sm flex" id="users_check_all" type="checkbox"/>
                                    </th>
                                    <th class="min-w-[250px]">Kullanıcı</th>
                                    <th class="min-w-[220px]">E-posta</th>
                                    <th class="min-w-[220px]">Roller</th>
                                    <th class="min-w-[150px]">Durum</th>
                                    <th class="min-w-[160px]">Oluşturulma</th>
                                    <th class="w-[60px]"></th>
                                    <th class="w-[60px]"></th>
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($users as $user)
                                    <tr data-row-id="{{ $user->id }}">
                                        <td>
                                            <input class="kt-checkbox kt-checkbox-sm users_row_check"
                                                   type="checkbox" value="{{ $user->id }}"/>
                                        </td>

                                        <td>
                                            <div class="flex flex-col gap-0.5">
                                                <span class="font-medium text-sm">{{ $user->name }}</span>
                                                <span class="text-sm text-secondary-foreground">#{{ $user->id }}</span>
                                            </div>
                                        </td>

                                        <td class="text-sm text-foreground font-normal">{{ $user->email }}</td>

                                        <td class="text-sm text-secondary-foreground font-normal">
                                            {{ $user->roles->pluck('name')->join(', ') }}
                                        </td>

                                        <td>
                                            @if($user->is_active)
                                                <span class="kt-badge kt-badge-success">Aktif</span>
                                            @else
                                                <span class="kt-badge kt-badge-danger">Pasif</span>
                                            @endif
                                        </td>

                                        <td class="text-sm text-secondary-foreground font-normal">
                                            {{ $user->created_at->format('d.m.Y') }}
                                        </td>

                                        <td>
                                            @perm('users.update')
                                                <a href="{{ route('admin.users.edit', $user) }}"
                                                   class="kt-btn kt-btn-sm kt-btn-icon kt-btn-primary">
                                                    <i class="ki-filled ki-notepad-edit"></i>
                                                </a>
                                            @endperm
                                        </td>

                                        <td>
                                            @perm('users.delete')
                                                <form method="POST"
                                                      data-confirm="delete-user"
                                                      action="{{ route('admin.users.destroy', $user) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-destructive">
                                                        <i class="ki-filled ki-trash"></i>
                                                    </button>
                                                </form>
                                            @endperm
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>

                            {{-- Empty UI template (DataTables için HTML olarak kullanacağız) --}}
                            <template id="dt-empty-users">
                                <tr data-kt-empty-row="true">
                                    <td colspan="8" class="py-12">
                                        <div class="flex flex-col items-center justify-center gap-2 text-center text-muted-foreground">
                                            <i class="ki-outline ki-folder-open text-4xl mb-2"></i>
                                            <div class="font-medium text-secondary-foreground">Henüz kayıt bulunmuyor.</div>
                                            <div class="text-sm">Yeni kayıt ekleyerek başlayabilirsiniz.</div>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <template id="dt-zero-users">
                                <tr data-kt-zero-row="true">
                                    <td colspan="8" class="py-12">
                                        <div class="flex flex-col items-center justify-center gap-2 text-center text-muted-foreground">
                                            <i class="ki-outline ki-search-list text-4xl mb-2"></i>
                                            <div class="font-medium text-secondary-foreground">Sonuç bulunamadı.</div>
                                            <div class="text-sm">Arama kriterlerini değiştirip tekrar deneyin.</div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </div>

                        <div class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
                            <div class="flex items-center gap-2 order-2 md:order-1">
                                Göster
                                <select class="kt-select w-16" id="usersPageSize"></select>
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
