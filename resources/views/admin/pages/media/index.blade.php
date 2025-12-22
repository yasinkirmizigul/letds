@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed" data-page="media.index">

        @includeIf('admin.partials._flash')

        <div class="flex items-center justify-between mb-5">
            <div>
                <h1 class="text-xl font-semibold">Medya Kütüphanesi</h1>
                <div class="text-sm text-muted-foreground">Yükle, seç, modüllerde tekrar kullan.</div>
            </div>

            <button class="kt-btn kt-btn-primary"
                    type="button"
                    data-kt-modal-toggle="#mediaUploadModal">
                <i class="ki-filled ki-file-up"></i>
                Yükle
            </button>
        </div>

        <div class="kt-card">
            <div class="kt-card-header flex-wrap gap-3">
                <div class="flex items-center gap-2 grow">
                    <i class="ki-filled ki-magnifier text-muted-foreground"></i>
                    <input id="mediaSearch"
                           type="text"
                           class="kt-input w-full"
                           placeholder="Ara: dosya adı, mime type..." />
                </div>

                <div class="flex items-center gap-2">
                    <select id="mediaType" class="kt-select w-44"
                            data-kt-select="true"
                            data-kt-select-placeholder="Medya Tipi"
                            data-kt-select-config='{
                                "optionsClass": "kt-scrollable overflow-auto max-h-[250px]"
                            }'>
                        <option value="">Tümü</option>
                        <option value="image">Görsel</option>
                        <option value="video">Video</option>
                        <option value="doc">Doküman</option>
                    </select>
                </div>
            </div>

            <div class="kt-card-content">
                <div id="mediaGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"></div>

                <div id="mediaEmpty" class="hidden">
                    <div class="flex flex-col items-center text-center gap-2">
                        <div class="font-semibold">Henüz medya yok</div>
                        <div class="text-sm text-muted-foreground">Yükleyerek başlayabilirsin.</div>
                        <button class="kt-btn kt-btn-light mt-4"
                                type="button"
                                data-kt-modal-toggle="#mediaUploadModal">
                            Yükle
                        </button>
                    </div>
                </div>
            </div>

            <div class="kt-card-footer flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div id="mediaInfo" class="text-sm text-muted-foreground"></div>
                <div id="mediaPagination" class="kt-datatable-pagination"></div>
            </div>
        </div>

        @include('admin.pages.media.partials._upload-modal')
    </div>
@endsection
