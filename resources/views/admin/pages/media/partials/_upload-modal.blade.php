<div class="kt-modal kt-modal-center" id="mediaUploadModal" data-kt-modal="true">
    <div class="kt-modal-content  max-w-[25%]">
        <div class="kt-modal-header">
            <h3 class="kt-modal-title">Medya Yükle</h3>
            <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" data-kt-modal-dismiss="true">
                <i class="ki-outline ki-cross"></i>
            </button>
        </div>

        <div class="kt-modal-body p-6">
            <div class="grid gap-4">
                <input id="mediaFile" type="file" class="kt-input"/>
                <input id="mediaTitle" type="text" class="kt-input" placeholder="Başlık (opsiyonel)">
                <input id="mediaAlt" type="text" class="kt-input" placeholder="Alt (opsiyonel)">
                <div class="kt-alert kt-alert-light hidden" id="mediaUploadError"></div>
            </div>
        </div>

        <div class="kt-modal-footer justify-end gap-2">
            <button class="kt-btn kt-btn-light" data-kt-modal-dismiss="true">Kapat</button>
            <button class="kt-btn kt-btn-primary" id="mediaUploadBtn">
                <i class="ki-outline ki-cloud-add"></i> Yükle
            </button>
        </div>
    </div>

</div>
