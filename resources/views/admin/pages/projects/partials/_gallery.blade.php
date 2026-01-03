{{-- Project Galleries (Blog style) --}}
@php
    // slotlar
    $slots = [
        'main' => 'Ana',
        'sidebar' => 'Sidebar',
    ];
@endphp

<div class="grid gap-5 lg:gap-7.5">
    {{-- Sol: slot listeleri --}}
    <div class="lg:col-span-2">
        <div class="kt-card">
            <div class="kt-card-header py-4">
                <h3 class="kt-card-title">Galeriler</h3>

                <div class="kt-card-toolbar flex items-center gap-2">
                    <button type="button"
                            class="kt-btn kt-btn-light"
                            data-kt-modal-toggle="#projectGalleryPickerModal">
                        <i class="ki-outline ki-folder"></i> Galeri Ekle
                    </button>
                </div>
            </div>

            <div class="kt-card-content p-5 grid gap-6">

                {{-- Empty --}}
                <div id="projectGalleriesEmpty"
                     class="hidden text-sm text-muted-foreground">
                    Henüz galeri eklenmemiş.
                </div>

                {{-- Slot containers --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="grid gap-3">
                        <div class="font-medium">Ana</div>
                        <div id="projectGalleriesMain"
                             class="grid gap-3"
                             data-slot="main"></div>
                    </div>

                    <div class="grid gap-3">
                        <div class="font-medium">Sidebar</div>
                        <div id="projectGalleriesSidebar"
                             class="grid gap-3"
                             data-slot="sidebar"></div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- Picker Modal --}}
<div class="kt-modal kt-modal-center" id="projectGalleryPickerModal" data-kt-modal="true">
    <div class="kt-modal-content max-w-[60%]" style="max-height: 97vh">
        <div class="kt-modal-header">
            <h3 class="kt-modal-title">Galeri Seç</h3>
            <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" data-kt-modal-dismiss="true">
                <i class="ki-outline ki-cross"></i>
            </button>
        </div>

        <div class="kt-modal-body overflow-hidden p-7">
            <div class="grid gap-4">

                <div class="flex flex-col md:flex-row md:items-center gap-3">
                    <div class="grow">
                        <div class="flex flex-row kt-input-icon">
                            <i class="items-center ki-magnifier ki-outline me-2"></i>
                            <input id="projectGalleryPickerSearch"
                                   type="text"
                                   class="kt-input"
                                   placeholder="Galerilerde ara (ad/slug)...">
                        </div>
                    </div>

                    <div class="w-full md:w-56">
                        <select id="projectGalleryPickerSlot" class="kt-select w-full" data-kt-select="true" data-kt-select-placeholder="Galeri Tür">
                            <option value="main">Ana</option>
                            <option value="sidebar">Sidebar</option>
                        </select>
                    </div>
                </div>

                <div id="projectGalleryPickerInfo" class="text-xs text-muted-foreground"></div>
                <div id="projectGalleryPickerEmpty" class="hidden text-sm text-muted-foreground">Sonuç yok.</div>

                <div id="projectGalleryPickerList"
                     class="grid gap-3 kt-scrollable-y-auto"
                     style="max-height: 60vh"></div>

                <div id="projectGalleryPickerPagination"
                     class="flex items-center justify-center"></div>

            </div>
        </div>


        <div class="kt-modal-footer justify-end gap-2">
            <button type="button" class="kt-btn kt-btn-light" data-kt-modal-dismiss="true">Kapat</button>
        </div>
    </div>
</div>

{{-- Card template (JS render için referans) --}}
<template id="projectGalleryCardTpl">
    <div class="kt-card">
        <div class="kt-card-content p-4 flex items-start gap-3">
            <div class="grow grid">
                <div class="font-medium" data-title></div>
                <div class="text-xs text-muted-foreground" data-sub></div>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" class="kt-btn kt-btn-sm kt-btn-light" data-move title="Slot değiştir">
                    <i class="ki-outline ki-arrow-left-right"></i>
                </button>
                <button type="button" class="kt-btn kt-btn-sm kt-btn-light" data-remove title="Kaldır">
                    <i class="ki-outline ki-trash"></i>
                </button>
                <button type="button" class="kt-btn kt-btn-sm kt-btn-light" data-drag title="Sırala">
                    <i class="ki-outline ki-menu"></i>
                </button>
            </div>
        </div>
    </div>
</template>
