<div class="kt-modal" id="mediaPickerModal" data-kt-modal="true">
    <div class="kt-modal-dialog max-w-5xl">
        <div class="kt-modal-content">
            <div class="kt-modal-header">
                <h3 class="kt-modal-title">Medya Seç</h3>
                <button type="button"
                        class="hidden"
                        id="mediaPickerOpener"
                        data-kt-modal-toggle="#mediaPickerModal"></button>
                <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" data-kt-modal-dismiss="true">
                    <i class="ki-outline ki-cross"></i>
                </button>
            </div>

            <div class="kt-modal-body p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="kt-input-group w-[320px]">
                        <span class="kt-input-group-text"><i class="ki-outline ki-magnifier"></i></span>
                        <input id="mediaPickerSearch" class="kt-input" placeholder="Ara...">
                    </div>

                    <select id="mediaPickerType" class="kt-select w-[180px]" data-kt-select="true">
                        <option value="">Tümü</option>
                        <option value="image">Görsel</option>
                        <option value="video">Video</option>
                        <option value="pdf">Doküman</option>
                    </select>
                </div>

                <div id="mediaPickerGrid" class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-4"></div>
            </div>

            <div class="kt-modal-footer justify-end gap-2">
                <button class="kt-btn kt-btn-light" data-kt-modal-dismiss="true">Kapat</button>
            </div>
        </div>
    </div>
</div>
