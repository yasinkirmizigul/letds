@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed" data-page="permissions.index">
        @include('admin.partials._flash')

        <div class="flex items-center justify-between mb-4">
            <h1 class="text-lg font-semibold">Yetkiler</h1>
        </div>

        <div class="grid gap-5 lg:gap-7.5">
            <div class="kt-card kt-card-grid min-w-full">
                <div class="kt-card-header py-5 flex-wrap gap-4">
                    <h3 class="kt-card-title">Yetkiler</h3>

                    {{-- (Opsiyonel ama “tam uyum” için önerilir) --}}
                    <div class="flex items-center gap-2">
                        <input
                            id="permissionsSearch"
                            type="text"
                            class="kt-input kt-input-sm"
                            placeholder="Yetki / anahtar ara..."
                        />
                        @if(auth()->user()->hasPermission('permissions.create'))
                            <a href="{{ route('admin.permissions.create') }}" class="kt-btn kt-btn-sm kt-btn-primary">
                                Yetki Ekle
                            </a>
                        @endif
                    </div>
                </div>

                <div class="kt-card-content">
                    <div class="grid" id="permissions_dt">

                        <div class="kt-scrollable-x-auto">
                            <table class="kt-table table-auto kt-table-border w-full" id="permissions_table">
                                <thead>
                                <tr>
                                    <th class="min-w-[320px]">Yetki</th>
                                    <th class="min-w-[220px]">Anahtar</th>
                                    <th class="min-w-[160px]">Oluşturulma</th>
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($permissions as $permission)
                                    <tr>
                                        <td class="font-medium">{{ $permission->name }}</td>
                                        <td class="text-sm text-secondary-foreground">{{ $permission->slug }}</td>
                                        <td class="text-sm text-secondary-foreground">{{ $permission->created_at->format('d.m.Y') }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>

                            <template id="dt-empty-permissions">
                                <tr data-kt-empty-row="true">
                                    <td colspan="3" class="py-12">
                                        <div class="flex flex-col items-center justify-center gap-2 text-center text-muted-foreground">
                                            <i class="ki-outline ki-folder-open text-4xl mb-2"></i>
                                            <div class="font-medium text-secondary-foreground">Henüz kayıt bulunmuyor.</div>
                                            <div class="text-sm">Yeni yetki ekleyerek başlayabilirsiniz.</div>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <template id="dt-zero-permissions">
                                <tr data-kt-zero-row="true">
                                    <td colspan="3" class="py-12">
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
                                <select class="kt-select w-16" id="permissionsPageSize" name="perpage"></select>
                                / sayfa
                            </div>

                            <div class="flex items-center gap-4 order-1 md:order-2">
                                <span id="permissionsInfo"></span>
                                <div class="kt-datatable-pagination" id="permissionsPagination"></div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
