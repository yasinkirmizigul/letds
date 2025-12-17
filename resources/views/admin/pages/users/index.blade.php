@extends('admin.layouts.main.app')
@section('content')
    <div class="kt-container-fixed">

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
                            placeholder="Rol / yetki ara..."
                        />

                        @if(auth()->user()->hasPermission('users.create'))
                            <a href="{{ route('admin.users.create') }}" class="kt-btn kt-btn-sm kt-btn-primary">
                                Kullanıcı Ekle
                            </a>
                        @endif
                    </div>
                </div>

                <div class="kt-card-content">
                    <div class="grid" id="users_dt">

                        <div class="kt-scrollable-x-auto">
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
                                            @if(auth()->user()->hasPermission('users.update'))
                                                <a href="{{ route('admin.users.edit', $user) }}"
                                                   class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost">
                                                    <i class="ki-filled ki-notepad-edit"></i>
                                                </a>
                                            @endif
                                        </td>

                                        <td>
                                            @if(auth()->user()->hasPermission('users.delete'))
                                                <form method="POST"
                                                      action="{{ route('admin.users.destroy', $user) }}"
                                                      onsubmit="return confirm('Bu kullanıcıyı silmek istiyor musunuz?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost">
                                                        <i class="ki-filled ki-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>

                            {{-- Empty UI template (DataTables için HTML olarak kullanacağız) --}}
                            <template id="dt-empty-users">
                                <div class="flex flex-col items-center justify-center gap-2 text-center py-12">
                                    <i class="ki-outline ki-search-list text-4xl text-muted-foreground"></i>
                                    <div class="font-medium">Henüz kayıt bulunmuyor.</div>
                                    <div class="text-sm text-muted-foreground">
                                        Yeni kayıt ekleyerek başlayabilirsiniz.
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
                            <div class="flex items-center gap-2 order-2 md:order-1">
                                Göster
                                <select class="kt-select w-16" id="usersPageSize" name="perpage"></select>
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

@push('page_js')
    <script>
        (function () {
            function renderPagination(api, hostSelector) {
                const host = document.querySelector(hostSelector);
                if (!host) return;

                const info = api.page.info();
                const pages = info.pages;
                const page = info.page;

                host.innerHTML = '';
                if (pages <= 1) return;

                const makeBtn = (label, targetPage, disabled = false, active = false) => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = active ? 'kt-btn kt-btn-sm kt-btn-primary' : 'kt-btn kt-btn-sm kt-btn-light';
                    if (disabled) btn.disabled = true;
                    btn.textContent = label;
                    btn.addEventListener('click', () => api.page(targetPage).draw('page'));
                    return btn;
                };

                host.appendChild(makeBtn('‹', Math.max(0, page - 1), page === 0));

                const start = Math.max(0, page - 2);
                const end = Math.min(pages - 1, page + 2);
                for (let i = start; i <= end; i++) host.appendChild(makeBtn(String(i + 1), i, false, i === page));

                host.appendChild(makeBtn('›', Math.min(pages - 1, page + 1), page === pages - 1));
            }

            document.addEventListener('DOMContentLoaded', () => {
                initDataTable({
                    table: '#users_table',
                    search: '#usersSearch',
                    pageSize: '#usersPageSize',
                    info: '#usersInfo',
                    pagination: '#usersPagination',

                    pageLength: 10,
                    lengthMenu: [5, 10, 25, 50],
                    order: [[1, 'asc']],
                    dom: 't',

                    emptyTemplate: '#dt-empty-users',
                    zeroFallback: `
                      <div class="flex flex-col items-center justify-center gap-2 text-center py-12">
                        <i class="ki-outline ki-search-list text-4xl text-muted-foreground"></i>
                        <div class="font-medium">Sonuç bulunamadı.</div>
                        <div class="text-sm text-muted-foreground">
                          Arama kriterlerini değiştirip tekrar deneyin.
                        </div>
                      </div>
                    `,

                    columnDefs: [
                        { orderable: false, targets: [0, 6, 7] },
                        { searchable: false, targets: [0, 6, 7] },
                        { className: 'text-center', targets: [4] },
                    ],

                    // ✅ yeni: check-all desteği
                    checkAll: '#users_check_all',
                    rowChecks: '.users_row_check',
                });
            });
        })();
    </script>
@endpush
