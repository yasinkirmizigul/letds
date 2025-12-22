<div class="kt-modal kt-modal-center" id="mediaUploadModal" data-kt-modal="true">
    <div class="kt-modal-content w-3xl">
        <div class="kt-modal-header">
            <h3 class="kt-modal-title">Medya</h3>
            <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" data-kt-modal-dismiss="true">
                <i class="ki-outline ki-cross"></i>
            </button>
        </div>

        <div class="kt-modal-body p-0">
            {{-- Tabs --}}
            <div class="border-b border-b-border px-6 pt-4">
                <div class="flex items-center gap-2">
                    <button type="button"
                            class="kt-btn kt-btn-sm kt-btn-light"
                            data-media-tab="upload"
                            aria-selected="true">
                        <i class="ki-outline ki-file-up"></i> Yükleme
                    </button>

                    <button type="button"
                            class="kt-btn kt-btn-sm kt-btn-light"
                            data-media-tab="library"
                            aria-selected="false">
                        <i class="ki-outline ki-folder"></i> Kütüphane
                    </button>

                    <div class="ms-auto text-xs text-muted-foreground px-1">
                        Çoklu yükleme • Retry • Hata detayı
                    </div>
                </div>
            </div>

            <div class="p-6">
                {{-- UPLOAD PANE --}}
                <div id="mediaUploadPane">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="flex items-center gap-2 grow">
                            <i class="ki-filled ki-magnifier text-muted-foreground"></i>
                            <input id="mediaLibrarySearch" type="text" class="kt-input w-full" placeholder="Kütüphanede ara..." />
                        </div>

                        <select id="mediaLibraryType" class="kt-select w-40">
                            <option value="">Tümü</option>
                            <option value="image">Görsel</option>
                            <option value="video">Video</option>
                            <option value="doc">Doküman</option>
                        </select>
                    </div>

                    <div id="mediaLibraryResults" class="grid gap-2"></div>
                    <div class="grid gap-4">
                        <div id="mediaDropzone"
                             class="rounded-xl border border-dashed border-border bg-muted/10 p-6 cursor-pointer hover:bg-muted/20 transition">
                            <div class="flex items-center gap-3">
                                <div class="size-10 rounded-lg bg-muted flex items-center justify-center">
                                    <i class="ki-outline ki-cloud-add text-xl"></i>
                                </div>
                                <div class="grid">
                                    <div class="font-medium text-mono">Sürükle-bırak</div>
                                    <div class="text-xs text-muted-foreground">
                                        Dosyaları buraya bırak veya tıkla seç. (20MB / dosya)
                                    </div>
                                </div>
                                <div class="ms-auto">
                                    <span class="kt-badge kt-badge-outline">Multi</span>
                                </div>
                            </div>

                            <input id="mediaFiles"
                                   type="file"
                                   class="hidden"
                                   multiple>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <input id="mediaTitle" type="text" class="kt-input" placeholder="Başlık (opsiyonel, tüm dosyalara)">
                            <input id="mediaAlt" type="text" class="kt-input" placeholder="Alt (opsiyonel, tüm dosyalara)">
                        </div>

                        <div id="mediaUploadError" class="hidden text-sm text-destructive whitespace-pre-wrap"></div>

                        <div class="flex items-center justify-between">
                            <div class="text-xs text-muted-foreground">
                                Kuyruk: <span id="mediaQueueInfo">0</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" class="kt-btn kt-btn-light" id="mediaClearQueue">
                                    Temizle
                                </button>
                                <button type="button" class="kt-btn kt-btn-primary" id="mediaStartUpload">
                                    <i class="ki-outline ki-cloud-add"></i> Yüklemeyi Başlat
                                </button>
                            </div>
                        </div>

                        <div id="mediaUploadList" class="grid gap-2"></div>
                    </div>
                </div>

                {{-- LIBRARY PANE --}}
                <div id="mediaLibraryPane" class="hidden">
                    <div class="flex items-center justify-between mb-3">
                        <div class="font-semibold text-mono">Son yüklenenler</div>
                        <button type="button" class="kt-btn kt-btn-sm kt-btn-light" id="mediaRefreshLibrary">
                            <i class="ki-outline ki-arrows-circle"></i> Yenile
                        </button>
                    </div>

                    <div id="mediaRecentList" class="grid gap-2"></div>

                    <div class="mt-4 text-xs text-muted-foreground">
                        Not: Kütüphane grid’i sayfada zaten var. Buradaki liste sadece “modal içi hızlı erişim”.
                    </div>
                </div>
            </div>
        </div>

        <div class="kt-modal-footer justify-end gap-2">
            <button class="kt-btn kt-btn-light" data-kt-modal-dismiss="true">Kapat</button>
        </div>
    </div>
</div>
