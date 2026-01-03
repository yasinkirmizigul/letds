{{-- Global Media Picker Modal --}}
<div class="kt-modal hidden" id="mediaPickerModal">
    {{-- media-picker.js bunu click’leyerek açıyor --}}
    <button type="button" class="hidden" data-kt-modal-toggle="#mediaPickerModal"></button>

    <div class="kt-modal-dialog max-w-5xl">
        <div class="kt-modal-content">
            <div class="kt-modal-header">
                <h3 class="kt-modal-title">Medya Seç</h3>

                <button type="button"
                        class="kt-btn kt-btn-sm kt-btn-light"
                        data-kt-modal-dismiss="true">
                    Kapat
                </button>
            </div>

            <div class="kt-modal-body p-6 flex flex-col gap-4">
                <div class="flex flex-col md:flex-row md:items-center gap-3">
                    <input class="kt-input flex-1"
                           id="mediaPickerSearch"
                           placeholder="Ara..."/>

                    <select class="kt-select w-full md:w-[220px]" id="mediaPickerType" data-kt-select="true" data-kt-select-placeholder="Medya Türü">
                        <option value="">Tümü</option>
                        <option value="image">Görsel</option>
                        <option value="video">Video</option>
                        <option value="pdf">Doküman</option>
                    </select>
                </div>

                <div id="mediaPickerGrid" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3"></div>
            </div>
        </div>
    </div>
</div>
