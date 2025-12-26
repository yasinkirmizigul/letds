@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[90%]"
         data-page="trash.index"
         data-perpage="25">

        <div class="grid gap-5 lg:gap-7.5">
            @includeIf('admin.partials._flash')

            <div class="kt-card kt-card-grid min-w-full">
                <div class="kt-card-header py-5 flex-wrap gap-4">
                    <div class="flex flex-col">
                        <h3 class="kt-card-title">{{ $pageTitle ?? 'Silinenler (Trash)' }}</h3>
                        <div class="text-sm text-muted-foreground">Media / Blog / Category tek ekrandan restore / kalıcı sil</div>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">
                        <input id="trashSearch" type="text" class="kt-input kt-input-sm" placeholder="Ara..." />

                        <select id="trashType" class="kt-select w-44" data-kt-select="true">
                            <option value="all">Tümü</option>
                            <option value="media">Media</option>
                            <option value="blog">Blog</option>
                            <option value="category">Category</option>
                        </select>
                    </div>
                </div>

                <div class="kt-card-content">
                    {{-- Bulk bar --}}
                    <div id="trashBulkBar" class="hidden kt-card mb-4">
                        <div class="kt-card-content p-3 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" class="kt-checkbox kt-checkbox-sm" id="trash_check_all">
                                    <span>Tümünü seç</span>
                                </label>
                                <span class="text-sm text-muted-foreground">
                                    Seçili: <b id="trashSelectedCount">0</b>
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="button" class="kt-btn kt-btn-sm kt-btn-success" id="trashBulkRestoreBtn" disabled>
                                    <i class="ki-outline ki-arrow-circle-left"></i> Geri Yükle
                                </button>

                                <button type="button" class="kt-btn kt-btn-sm kt-btn-destructive" id="trashBulkForceDeleteBtn" disabled>
                                    <i class="ki-outline ki-trash"></i> Kalıcı Sil
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden">
                        <table class="kt-table table-auto kt-table-border w-full" id="trash_table">
                            <thead>
                            <tr>
                                <th class="w-[55px] dt-orderable-none">
                                    <input class="kt-checkbox kt-checkbox-sm" id="trash_check_all_head" type="checkbox">
                                </th>
                                <th class="w-[140px]">Tür</th>
                                <th class="min-w-[360px]">Başlık</th>
                                <th class="min-w-[280px]">Detay</th>
                                <th class="min-w-[220px]">Silinme</th>
                                <th class="w-[220px]"></th>
                            </tr>
                            </thead>
                            <tbody id="trashTbody"></tbody>
                        </table>
                    </div>

                    <template id="dt-empty-trash">
                        <tr>
                            <td colspan="6" class="py-12">
                                <div class="flex flex-col items-center text-center gap-2">
                                    <i class="ki-outline ki-trash text-3xl text-muted-foreground"></i>
                                    <div class="font-semibold">Silinen kayıt yok</div>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template id="dt-zero-trash">
                        <tr>
                            <td colspan="6" class="py-12">
                                <div class="flex flex-col items-center text-center gap-2">
                                    <i class="ki-outline ki-magnifier text-3xl text-muted-foreground"></i>
                                    <div class="font-semibold">Sonuç bulunamadı</div>
                                    <div class="text-sm text-muted-foreground">Aramanı değiştirip tekrar dene.</div>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <div class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
                        <div class="flex items-center gap-2 order-2 md:order-1">
                            Göster
                            <select class="kt-select w-16" id="trashPageSize" data-kt-select="true"></select>
                            / sayfa
                        </div>

                        <div class="flex items-center gap-4 order-1 md:order-2">
                            <span id="trashInfo"></span>
                            <div class="kt-datatable-pagination" id="trashPagination"></div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
