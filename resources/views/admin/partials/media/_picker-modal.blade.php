{{-- Global Media Picker Modal --}}
<div class="kt-modal kt-modal-center"
     id="mediaPickerModal"
     data-kt-modal="true">

    <div class="kt-modal-content admin-media-modal w-[calc(100vw-1rem)] max-w-6xl sm:w-[calc(100vw-2rem)]">
        <div class="kt-modal-header">
            <h3 class="kt-modal-title">Medya Seç</h3>

            <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" data-kt-modal-dismiss="true">
                <i class="ki-outline ki-cross"></i>
            </button>
        </div>

        <div class="kt-modal-body min-h-0 overflow-y-auto p-4 sm:p-7">
            <div class="grid gap-4">

                <div class="flex flex-col md:flex-row md:items-center gap-3">
                    <div class="grow">
                        <div class="kt-input-icon flex flex-row">
                            <i class="items-center ki-magnifier ki-outline me-2"></i>
                            <input
                                id="mediaPickerSearch"
                                type="text"
                                class="kt-input"
                                placeholder="Ara...">
                        </div>
                    </div>

                    <div class="w-full md:w-56">
                        <select id="mediaPickerType"
                                class="kt-select w-full"
                                data-kt-select="true"
                                data-kt-select-placeholder="Medya Türü">
                            <option value="">Tümü</option>
                            <option value="image">Görsel</option>
                            <option value="video">Video</option>
                            <option value="pdf">Doküman</option>
                        </select>
                    </div>
                </div>

                <div id="mediaPickerGrid"
                     class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 kt-scrollable-y-auto"
                     style="max-height: 70vh;">
                    {{-- JS dolduracak --}}
                </div>

            </div>
        </div>

        <div class="kt-modal-footer justify-end gap-2">
            <button class="kt-btn kt-btn-light" data-kt-modal-dismiss="true">Kapat</button>
        </div>
    </div>
</div>
