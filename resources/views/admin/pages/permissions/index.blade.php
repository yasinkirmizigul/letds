@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed">
        @include('admin.partials._flash')

        <div class="flex items-center justify-between mb-4">
            <h1 class="text-lg font-semibold">Yetkiler</h1>
            <a class="kt-btn kt-btn-primary" href="{{ route('admin.permissions.create') }}">Yeni Yetki</a>
        </div>
        <div class="grid gap-5 lg:gap-7.5">
            <div class="kt-card kt-card-grid min-w-full">
                <div class="kt-card-header py-5 flex-wrap">
                    <h3 class="kt-card-title">Yetkiler</h3>
                </div>


                <div class="kt-card-content">
                    <div class="grid" data-kt-datatable="true" data-kt-datatable-page-size="10">
                        <div class="kt-scrollable-x-auto">
                            <table class="kt-table table-auto kt-table-border" data-kt-datatable-table="true"
                                   id="permissions_table">
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

