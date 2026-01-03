@php
    /** @var \App\Models\Admin\Project\Project $project */
    $project = $project;
@endphp

<div class="kt-card"
     id="projectGalleryCard"
     data-galleries-list-url="{{ route('admin.galleries.list') }}"
     data-project-galleries-index-url="{{ route('admin.projects.galleries.index', $project) }}"
     data-project-galleries-attach-url="{{ route('admin.projects.galleries.attach', $project) }}"
     data-project-galleries-detach-url="{{ route('admin.projects.galleries.detach', $project) }}"
     data-project-galleries-reorder-url="{{ route('admin.projects.galleries.reorder', $project) }}">

    <div class="kt-card-header py-5 flex-wrap gap-3">
        <div class="flex flex-col">
            <h3 class="kt-card-title">Galeriler</h3>
            <div class="text-sm text-muted-foreground">Projeye galeri bağla, slot seç, sırala.</div>
        </div>

        <div class="ms-auto flex items-center gap-2">
            <button type="button"
                    class="kt-btn kt-btn-primary"
                    data-kt-modal-target="#projectGalleryPickerModal">
                Ekle
            </button>
        </div>
    </div>

    <div class="kt-card-content p-6 flex flex-col gap-4">
        <div id="projectGalleriesEmpty" class="text-sm text-muted-foreground">
            Henüz galeri bağlı değil.
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div class="font-semibold">Main</div>
                    <span class="text-xs text-muted-foreground">Sürükle-bırak</span>
                </div>

                <div id="projectGalleriesMain"
                     class="border rounded-lg p-3 min-h-[120px] flex flex-col gap-2">
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-3">
                    <div class="font-semibold">Sidebar</div>
                    <span class="text-xs text-muted-foreground">Sürükle-bırak</span>
                </div>

                <div id="projectGalleriesSidebar"
                     class="border rounded-lg p-3 min-h-[120px] flex flex-col gap-2">
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Gallery picker modal (Blog’daki ile aynı UX) --}}
<div class="kt-modal hidden" id="projectGalleryPickerModal">
    <div class="kt-modal-dialog max-w-3xl">
        <div class="kt-modal-content">
            <div class="kt-modal-header">
                <h3 class="kt-modal-title">Galeri Seç</h3>
                <button type="button" class="kt-btn kt-btn-sm kt-btn-light" data-kt-modal-close>Kapat</button>
            </div>

            <div class="kt-modal-body p-6 flex flex-col gap-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                    <div class="md:col-span-2">
                        <label class="kt-form-label">Ara</label>
                        <input class="kt-input" id="projectGalleryPickerSearch" placeholder="Galeri adı / slug" />
                    </div>

                    <div>
                        <label class="kt-form-label">Slot</label>
                        <select class="kt-select" id="projectGalleryPickerSlot">
                            <option value="main">Main</option>
                            <option value="sidebar">Sidebar</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <button type="button" class="kt-btn kt-btn-light" id="projectGalleryPickerRefresh">Yenile</button>

                    <div class="flex items-center gap-3 text-sm text-muted-foreground">
                        <span id="projectGalleryPickerInfo">0-0 / 0</span>
                        <div class="kt-datatable-pagination" id="projectGalleryPickerPagination"></div>
                    </div>
                </div>

                <div id="projectGalleryPickerEmpty" class="text-sm text-muted-foreground">
                    Kayıt yok.
                </div>

                <div class="border rounded-lg overflow-hidden">
                    <div id="projectGalleryPickerList" class="divide-y"></div>
                </div>
            </div>
        </div>
    </div>
</div>
