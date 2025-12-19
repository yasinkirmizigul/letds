@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed" data-page="media.index">
        <div class="grid gap-5 lg:gap-7.5">

            @includeIf('admin.partials._flash')

            <div class="kt-card kt-card-grid min-w-full">
                <div class="kt-card-header py-5 flex-wrap gap-4">
                    <div class="flex flex-col">
                        <h3 class="kt-card-title">Medya Kütüphanesi</h3>
                        <div class="text-sm text-muted-foreground">Yükle, seç, modüllerde tekrar kullan.</div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <div class="kt-input-group w-[260px]">
                            <span class="kt-input-group-text"><i class="ki-outline ki-magnifier"></i></span>
                            <input id="mediaSearch" type="text" class="kt-input" placeholder="Ara (isim, başlık, alt)">
                        </div>

                        <select id="mediaType" class="kt-select w-[160px]">
                            <option value="">Tümü</option>
                            <option value="image">Görsel</option>
                            <option value="video">Video</option>
                            <option value="doc">Doküman</option>
                        </select>

                        <button class="kt-btn kt-btn-primary" data-kt-modal-toggle="#mediaUploadModal">
                            <i class="ki-outline ki-cloud-add"></i>
                            Yükle
                        </button>
                    </div>
                </div>

                <div class="kt-card-content p-6">
                    <div id="mediaGrid" class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-4"></div>

                    <div id="mediaEmpty" class="hidden">
                        <div class="kt-card border-dashed">
                            <div class="kt-card-content py-12 flex flex-col items-center text-center gap-3">
                                <i class="ki-outline ki-folder text-3xl"></i>
                                <div class="font-semibold">Henüz medya yok</div>
                                <div class="text-sm text-muted-foreground">Yükleyerek başlayabilirsin.</div>
                                <button class="kt-btn kt-btn-light" data-kt-modal-toggle="#mediaUploadModal">Yükle</button>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mt-6">
                        <div class="text-sm text-muted-foreground" id="mediaInfo"></div>
                        <div class="kt-datatable-pagination" id="mediaPagination"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.pages.media.partials._upload-modal')
@endsection
