@extends('admin.layouts.main.app')

@section('content')
    @php($isTrash = ($mode ?? 'active') === 'trash')

    <div class="kt-container-fixed max-w-[90%]"
         data-page="media.index"
         data-mode="{{ $mode ?? 'active' }}">
        <div class="grid gap-5 lg:gap-7.5">

            <div class="kt-card">
                <div class="kt-card-header py-5 flex-wrap gap-4">
                    <div class="flex flex-col">
                        <h3 class="kt-card-title">{{ $isTrash ? 'Silinen Medyalar' : 'Medya Kütüphanesi' }}</h3>
                        <div class="text-sm text-muted-foreground">Yükle, ara, filtrele, seç ve yönet</div>
                    </div>

                    <div class="flex items-center gap-2 ms-auto">
                        <a href="{{ route('admin.media.index') }}"
                           class="kt-btn {{ $isTrash ? 'kt-btn-light' : 'kt-btn-primary' }}">
                            Medya
                        </a>

                        <a href="{{ route('admin.media.trash') }}"
                           class="kt-btn {{ $isTrash ? 'kt-btn-primary' : 'kt-btn-light' }}">
                            Silinenler
                        </a>

                        <input id="mediaSearch" class="kt-input" placeholder="Ara..." />

                        <select id="mediaType" class="kt-select"
                                data-kt-select="true"
                                data-kt-select-placeholder="Medya Tipi"
                                data-kt-select-config='{"optionsClass":"kt-scrollable overflow-auto max-h-[250px]"}'>
                            <option value="">Tümü</option>
                            <option value="image">Görsel</option>
                            <option value="video">Video</option>
                            <option value="pdf">PDF</option>
                        </select>

                        @if(!$isTrash)
                            <button type="button" class="kt-btn kt-btn-primary" data-kt-modal-toggle="#mediaUploadModal">
                                <i class="ki-outline ki-cloud-add"></i> Yükle
                            </button>
                        @endif
                    </div>
                </div>

                <div class="kt-card-content p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div id="mediaInfo" class="text-sm text-muted-foreground"></div>
                        <div id="mediaPagination" class="kt-datatable-pagination"></div>
                    </div>

                    {{-- Bulk bar --}}
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

                            <div class="flex items-center gap-2">
                                @if($isTrash)
                                    <button type="button" class="kt-btn kt-btn-sm kt-btn-success" id="mediaBulkRestoreBtn" disabled>
                                        <i class="ki-outline ki-arrow-circle-left"></i> Geri Yükle
                                    </button>
                                    <button type="button" class="kt-btn kt-btn-sm kt-btn-danger" id="mediaBulkForceDeleteBtn" disabled>
                                        <i class="ki-outline ki-trash"></i> Kalıcı Sil
                                    </button>
                                @else
                                    <button type="button" class="kt-btn kt-btn-sm kt-btn-danger" id="mediaBulkDeleteBtn" disabled>
                                        <i class="ki-outline ki-trash"></i> Seçilenleri sil
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div id="mediaGrid" class="grid grid-cols-1 sm:grid-cols-4 lg:grid-cols-6 gap-4"></div>

                    <div id="mediaEmpty" class="hidden">
                        <div class="kt-card mt-4">
                            <div class="kt-card-content p-10 text-center text-muted-foreground">
                                Medya bulunamadı.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Upload modal sadece active modda gerekli ama kalabilir; istersen if ile kapatırsın --}}
            @include('admin.pages.media.partials._upload-modal')

        </div>
    </div>
@endsection
