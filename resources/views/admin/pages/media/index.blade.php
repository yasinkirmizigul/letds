@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed" data-page="media.index">
        <div class="grid gap-5 lg:gap-7.5">

            <div class="kt-card">
                <div class="kt-card-header py-5 flex-wrap gap-4">
                    <div class="flex flex-col">
                        <h3 class="kt-card-title">Medya Kütüphanesi</h3>
                        <div class="text-sm text-muted-foreground">Yükle, ara, filtrele, seç ve yönet</div>
                    </div>

                    <div class="flex items-center gap-2 ms-auto">
                        <input id="mediaSearch" class="kt-input" placeholder="Ara..." />
                        <select id="mediaType" class="kt-select"  data-kt-select="true"
                                data-kt-select-placeholder="Medya Tipi"
                                data-kt-select-config='{
			"optionsClass": "kt-scrollable overflow-auto max-h-[250px]"
		}'>
                            <option value="">Tümü</option>
                            <option value="image">Görsel</option>
                            <option value="video">Video</option>
                            <option value="pdf">PDF</option>
                        </select>

                        <button type="button" class="kt-btn kt-btn-primary" data-kt-modal-toggle="#mediaUploadModal">
                            <i class="ki-outline ki-cloud-add"></i> Yükle
                        </button>
                    </div>
                </div>

                <div class="kt-card-content p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div id="mediaInfo" class="text-sm text-muted-foreground"></div>
                        <div id="mediaPagination" class="kt-datatable-pagination"></div>
                    </div>

                    {{-- ✅ Bulk bar --}}
                    <div id="mediaBulkBar" class="hidden kt-card mb-4">
                        <div class="kt-card-content p-3 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" class="kt-checkbox kt-checkbox-sm" id="mediaCheckAll">
                                    <span>Tümünü seç</span>
                                </label>
                                <span class="text-sm text-muted-foreground">
                                    Seçili: <b id="mediaSelectedCount">0</b>
                                </span>
                            </div>

                            <button type="button" class="kt-btn kt-btn-sm kt-btn-danger" id="mediaBulkDeleteBtn" disabled>
                                <i class="ki-outline ki-trash"></i> Seçilenleri sil
                            </button>
                        </div>
                    </div>

                    <div id="mediaGrid" class="grid grid-cols-1 sm:grid-cols-4 lg:grid-cols-4 gap-4"></div>

                    <div id="mediaEmpty" class="hidden">
                        <div class="kt-card mt-4">
                            <div class="kt-card-content p-10 text-center text-muted-foreground">
                                Medya bulunamadı.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @include('admin.pages.media.partials._upload-modal')
        </div>
    </div>
@endsection
