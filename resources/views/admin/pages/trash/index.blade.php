@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed"
         data-page="trash.index"
         data-perpage="{{ $perPage ?? 25 }}"
         data-list-url="{{ route('admin.trash.list') }}"
         data-bulk-restore-url="{{ route('admin.trash.bulkRestore') }}"
         data-bulk-force-delete-url="{{ route('admin.trash.bulkForceDestroy') }}">

        <div class="grid gap-5 lg:gap-7.5">

            @includeIf('admin.partials._flash')

            <div class="kt-card">
                <div class="kt-card-header py-5 flex-wrap gap-4">
                    <div class="flex flex-col">
                        <h3 class="kt-card-title text-lg font-semibold">Silinenler</h3>
                        <div class="text-sm text-muted-foreground">
                            Media / Blog / Category kayıtlarını tek ekrandan geri yükle veya kalıcı sil
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <input id="trashSearch"
                               class="kt-input w-[260px]"
                               placeholder="Ara..."/>

                        <select id="trashType" class="kt-select w-[180px]" data-kt-select="true" data-kt-select-placeholder="Silinen Türü Seç">
                            <option value="all">Tümü</option>
                            <option value="media">Medya</option>
                            <option value="blog">Blog</option>
                            <option value="category">Kategori</option>
                        </select>
                    </div>
                </div>

                {{-- Bulk bar --}}
                <div id="trashBulkBar" class="kt-card-content hidden border-b border-border py-3">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" class="kt-checkbox kt-checkbox-sm" id="trash_check_all">
                                <span class="text-sm">Tümünü seç</span>
                            </label>

                            <span class="text-sm text-muted-foreground">
                                Seçili: <span id="trashSelectedCount">0</span>
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" id="trashBulkRestoreBtn" class="kt-btn kt-btn-sm kt-btn-light" disabled>
                                Geri Yükle
                            </button>
                            <button type="button" id="trashBulkForceDeleteBtn" class="kt-btn kt-btn-sm kt-btn-danger" disabled>
                                Kalıcı Sil
                            </button>
                        </div>
                    </div>
                </div>

                <div class="kt-card-content p-0">
                    <div class="overflow-x-auto">
                        <table class="kt-table">
                            <thead>
                            <tr>
                                <th class="w-[55px]">
                                    <input class="kt-checkbox kt-checkbox-sm" id="trash_check_all_head" type="checkbox">
                                </th>
                                <th class="min-w-[160px]">Tür</th>
                                <th class="min-w-[320px]">Başlık</th>
                                <th class="min-w-[180px]">Silinme</th>
                                <th class="w-[160px] text-end">İşlem</th>
                            </tr>
                            </thead>
                            <tbody id="trashTbody"></tbody>
                        </table>
                    </div>

                    {{-- Empty templates --}}
                    <template id="dt-empty-trash">
                        <tr>
                            <td colspan="5" class="py-10 text-center text-muted-foreground">
                                Silinen kayıt yok
                            </td>
                        </tr>
                    </template>

                    <template id="dt-zero-trash">
                        <tr>
                            <td colspan="5" class="py-10 text-center">
                                <div class="font-medium">Sonuç bulunamadı</div>
                                <div class="text-sm text-muted-foreground">Aramanı değiştirip tekrar dene.</div>
                            </td>
                        </tr>
                    </template>
                </div>

                <div class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
                    <div class="flex items-center gap-2 order-2 md:order-1">
                        Göster
                        <select class="kt-select w-16" id="trashPageSize" name="perpage"></select>
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
@endsection
