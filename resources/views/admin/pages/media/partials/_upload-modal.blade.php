<div class="kt-modal kt-modal-center" id="mediaUploadModal" data-kt-modal="true">
    <div class="kt-modal-content max-w-[60%]" style="max-height: 97vh">
        <div class="kt-modal-header">
            <h3 class="kt-modal-title">Medya</h3>
            <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" data-kt-modal-dismiss="true">
                <i class="ki-outline ki-cross"></i>
            </button>
        </div>

        <div class="kt-modal-body overflow-hidden p-7">
            {{-- Tabs --}}
            <div class="border-b border-b-border px-6 py-4">
                <div class="flex items-center gap-2">
                    <button type="button"
                            id="mediaTabUpload"
                            class="kt-btn kt-btn-sm kt-btn-warning"
                            data-media-tab="upload"
                            aria-selected="true">
                        <i class="ki-outline ki-file-up"></i> Yükleme
                    </button>

                    <button type="button"
                            id="mediaTabLibrary"
                            class="kt-btn kt-btn-sm kt-btn-info"
                            data-media-tab="library"
                            aria-selected="false">
                        <i class="ki-outline ki-folder"></i> Kütüphane
                    </button>

                    <div class="ms-auto text-xs text-muted-foreground px-1">
                        Çoklu yükleme • Retry • Hata detayı
                    </div>
                </div>
            </div>

            <div class="pt-6">
                {{-- UPLOAD PANE --}}
                <div id="mediaUploadPane">
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

                        {{-- Global Title/Alt (opsiyonel) + Hepsine Uygula --}}
                        <div class="grid gap-3">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div class="grid gap-2">
                                    <input id="mediaTitle" type="text" class="kt-input" placeholder="Başlık (opsiyonel, varsayılan)">
                                    <button type="button" class="kt-btn kt-btn-sm kt-btn-light" id="mediaApplyTitleAll">
                                        <i class="ki-outline ki-copy"></i> Başlığı hepsine uygula
                                    </button>
                                </div>

                                <div class="grid gap-2">
                                    <input id="mediaAlt" type="text" class="kt-input" placeholder="Alt (opsiyonel, varsayılan)">
                                    <button type="button" class="kt-btn kt-btn-sm kt-btn-light" id="mediaApplyAltAll">
                                        <i class="ki-outline ki-copy"></i> Alt’ı hepsine uygula
                                    </button>
                                </div>
                            </div>

                            <div class="text-xs text-muted-foreground">
                                İpucu: Her dosya satırında ayrıca ayrı başlık/alt girebilirsin. Satır içi alan boşsa global değer kullanılır.
                            </div>
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
                                <button type="button" class="kt-btn kt-btn-success" id="mediaStartUpload">
                                    <i class="ki-outline ki-cloud-add"></i> Yüklemeyi Başlat
                                </button>
                            </div>
                        </div>

                        <div id="mediaUploadList" class="gap-2 grid kt-scrollable-y-auto" style="max-height: 52vh;"></div>
                    </div>
                </div>

                {{-- LIBRARY PANE --}}

                <div id="mediaLibraryPane" class="hidden">
                    <div class="grid gap-5">

                        {{-- ÜST BAR: Search + Filter + Refresh --}}
                        <div class="kt-card">
                            <div class="kt-card-content p-4 flex flex-col gap-4">
                                <div class="flex flex-col md:flex-row md:items-center gap-3">
                                    <div class="grow">
                                        <div class="flex flex-row kt-input-icon">
                                            <i class="items-center ki-magnifier ki-outline me-2"></i>
                                            <input
                                                id="mediaLibrarySearch"
                                                type="text"
                                                class="kt-input"
                                                placeholder="Kütüphanede ara (dosya adı, başlık, alt...)">
                                        </div>
                                    </div>

                                    <div class="w-full md:w-56">
                                        <select id="mediaLibraryType" class="kt-select w-full"
                                                data-kt-select="true"
                                                data-kt-select-placeholder="Medya Türü"
                                                data-kt-select-config='{
                                                "optionsClass": "kt-scrollable overflow-auto max-h-[250px]"
                                            }'>
                                            <option value="">Tümü</option>
                                            <option value="image">Görsel</option>
                                            <option value="video">Video</option>
                                            <option value="pdf">PDF</option>
                                        </select>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <button type="button" id="mediaRefreshLibrary" class="kt-btn kt-btn-light">
                                            <i class="ki-outline ki-arrows-circle"></i>
                                            Yenile
                                        </button>
                                    </div>
                                </div>

                                {{-- BULK BAR (başta gizli; JS açacak) --}}
                                <div id="mediaLibraryBulkBar" class="hidden">
                                    <div class="rounded-xl border border-border bg-background">
                                        <div class="px-4 py-3 flex flex-wrap items-center justify-between gap-3">
                                            <label class="flex items-center gap-2">
                                                <input type="checkbox" class="kt-checkbox kt-checkbox-sm" id="mediaLibraryCheckAll">
                                                <span class="text-sm">Tümünü seç</span>
                                            </label>

                                            <div class="flex items-center gap-3">
                                                <div class="text-sm">
                                                    Seçili: <span class="font-semibold" id="mediaLibrarySelectedCount">0</span>
                                                </div>

                                                <button type="button" class="kt-btn kt-btn-sm kt-btn-danger" id="mediaLibraryBulkDeleteBtn">
                                                    <i class="ki-outline ki-trash"></i>
                                                    Toplu Sil
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        {{-- SON YÜKLENENLER --}}
                        <div class="kt-card">
                            <div class="kt-card-header py-4">
                                <h3 class="kt-card-title">Son yüklenenler</h3>
                                <div class="kt-card-toolbar">
                                    <span class="text-xs text-muted-foreground">Bu liste tarayıcı (localStorage) bazlıdır</span>
                                </div>
                            </div>

                            <div class="kt-card-content px-4 pb-4">
                                <div id="mediaRecentList" class="flex flex-col gap-1">
                                    {{-- JS dolduracak --}}
                                    <div class="text-xs text-muted-foreground">Henüz yükleme yok.</div>
                                </div>
                            </div>
                        </div>

                        {{-- KÜTÜPHANE SONUÇLARI --}}
                        <div class="kt-card">
                            <div class="kt-card-header py-4">
                                <h3 class="kt-card-title">Kütüphane</h3>
                                <div class="kt-card-toolbar">
                                    <span class="text-xs text-muted-foreground">Seçim + tekli silme + toplu silme burada</span>
                                </div>
                            </div>

                            <div class="kt-card-content p-4">
                                <div id="mediaLibraryResults" class="grid gap-3">
                                    {{-- JS kartları basacak --}}
                                    {{--<div class="text-sm text-muted-foreground">Yükleniyor…</div>--}}
                                </div>

                                <div id="mediaLibraryPagination" class="mt-4 flex items-center justify-center">
                                    {{-- JS pagination basacak --}}
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>

        <div class="kt-modal-footer justify-end gap-2">
            <button class="kt-btn kt-btn-light" data-kt-modal-dismiss="true">Kapat</button>
        </div>
    </div>
</div>
