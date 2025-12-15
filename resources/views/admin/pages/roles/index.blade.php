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
                            type="text"
                            class="kt-input kt-input-sm"
                            placeholder="Rol / yetki ara..."
                            data-kt-datatable-search="#roles_table"
                        />

                        @if(auth()->user()->hasPermission('roles.create'))
                            <a href="{{ route('admin.roles.create') }}" class="kt-btn kt-btn-sm kt-btn-primary">
                                Rol Ekle
                            </a>
                        @endif
                    </div>
                </div>
                <div class="kt-card-content">
                    <div class="grid" data-kt-datatable="true" data-kt-datatable-page-size="10">
                        <div class="kt-scrollable-x-auto">
                            <table class="kt-table table-auto kt-table-border" data-kt-datatable-table="true" id="roles_table">
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
                                        <td class="text-sm text-secondary-foreground">{{ $role->permissions->pluck('name')->join(', ') }}</td>
                                        <td class="text-sm text-secondary-foreground">{{ $role->created_at->format('d.m.Y') }}</td>
                                        <td>
                                            @if(auth()->user()->hasPermission('roles.update'))
                                                <a href="{{ route('admin.roles.edit', $role) }}" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost">
                                                    <i class="ki-filled ki-notepad-edit"></i>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>


                        <div class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
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
