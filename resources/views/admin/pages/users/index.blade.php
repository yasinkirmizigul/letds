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
                        {{-- Hızlı arama (client-side) --}}
                        <input
                            type="text"
                            class="kt-input kt-input-sm"
                            placeholder="Rol / yetki ara..."
                            data-kt-datatable-search="#users_table"
                        />
                        @if(auth()->user()->hasPermission('users.create'))
                            <a href="{{ route('admin.users.create') }}" class="kt-btn kt-btn-sm kt-btn-primary">
                                Kullanıcı Ekle
                            </a>
                        @endif
                    </div>
                </div>
                <div class="kt-card-content">
                    <div class="grid" data-kt-datatable="true" data-kt-datatable-page-size="10">
                        <div class="kt-scrollable-x-auto">
                            <table class="kt-table table-auto kt-table-border" data-kt-datatable-table="true"
                                   id="users_table">
                                <thead>
                                <tr>
                                    <th class="w-[55px]">
                                        <input class="kt-checkbox kt-checkbox-sm" data-kt-datatable-check="true"
                                               type="checkbox"/>
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
                                    <tr>
                                        <td>
                                            <input class="kt-checkbox kt-checkbox-sm" data-kt-datatable-row-check="true"
                                                   type="checkbox" value="{{ $user->id }}"/>
                                        </td>
                                        <td>
                                            <div class="flex flex-col gap-0.5">
                                                <span class="font-medium text-sm">{{ $user->name }}</span>
                                                <span class="text-sm text-secondary-foreground">#{{ $user->id }}</span>
                                            </div>
                                        </td>
                                        <td class="text-sm text-foreground font-normal">{{ $user->email }}</td>
                                        <td class="text-sm text-secondary-foreground font-normal">{{ $user->roles->pluck('name')->join(', ') }}</td>
                                        <td>
                                            @if($user->is_active)
                                                <span class="kt-badge kt-badge-success">Aktif</span>
                                            @else
                                                <span class="kt-badge kt-badge-danger">Pasif</span>
                                            @endif
                                        </td>
                                        <td class="text-sm text-secondary-foreground font-normal">{{ $user->created_at->format('d.m.Y') }}</td>
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
                                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                                      onsubmit="return confirm('Bu kullanıcıyı silmek istiyor musunuz?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost">
                                                        <i class="ki-filled ki-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table><!-- EMPTY TEMPLATE -->
                            <template id="kt-empty-row-users">
                                <tr data-kt-empty-row="true">
                                    <td colspan="8" class="py-12">
                                        <div class="flex flex-col items-center justify-center gap-2 text-center">
                                            <i class="ki-outline ki-search-list text-4xl text-muted-foreground"></i>
                                            <div class="font-medium" data-title>Henüz kayıt bulunmuyor.</div>
                                            <div class="text-sm text-muted-foreground" data-desc>
                                                Yeni kayıt ekleyerek başlayabilirsiniz.
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </div>


                        <div
                            class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
                            <div class="flex items-center gap-2 order-2 md:order-1">
                                Göster
                                <select class="kt-select w-16" data-kt-datatable-size="true" name="perpage"></select>
                                / sayfa
                            </div>
                            <div class="flex items-center gap-4 order-1 md:order-2">
                                <span data-kt-datatable-info="true"></span>
                                <div class="kt-datatable-pagination" data-kt-datatable-pagination="true"></div>
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
            const table = document.querySelector('#users_table');
            if (!table) return;

            const tbody = table.querySelector('tbody');
            if (!tbody) return;

            const observer = new MutationObserver(() => {
                const rows = tbody.querySelectorAll('tr:not([data-kt-empty-row])');

                if (rows.length === 0) {
                    if (!tbody.querySelector('[data-kt-empty-row]')) {
                        const tpl = document.querySelector('#kt-empty-row-users');
                        if (tpl) {
                            tbody.appendChild(tpl.content.cloneNode(true));
                        }
                    }
                } else {
                    const empty = tbody.querySelector('[data-kt-empty-row]');
                    if (empty) empty.remove();
                }
            });

            observer.observe(tbody, { childList: true });
        });
    </script>
@endpush
