{{-- Media Picker Modal (initMediaPicker() bunu arıyor: #mediaPickerModal) --}}
<div class="kt-modal hidden" id="mediaPickerModal">
    <div class="kt-modal-dialog max-w-4xl">
        <div class="kt-modal-content">
            <div class="kt-modal-header">
                <h3 class="kt-modal-title">Medya Seç</h3>

                {{-- media-picker.js burayı click’leyip kapatıyor --}}
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

                    <select class="kt-select w-full md:w-[200px]" id="mediaPickerType">
                        <option value="">Tümü</option>
                        <option value="image">Görsel</option>
                        <option value="video">Video</option>
                        <option value="pdf">PDF</option>
                    </select>

                    {{-- media-picker.js bunu bulup tıklıyor: [data-kt-modal-toggle="#mediaPickerModal"] --}}
                    <button type="button"
                            class="hidden"
                            data-kt-modal-toggle="#mediaPickerModal">
                    </button>
                </div>

                <div id="mediaPickerGrid"
                     class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                    {{-- JS dolduracak --}}
                </div>
            </div>
        </div>
    </div>
</div>
