@php
    $id = $id ?? 'gm';
    $title = $title ?? 'Galeriler';
    $slots = $slots ?? ['main' => 'Ana Alan', 'sidebar' => 'Yan Alan'];

    $routes = $routes ?? [];
    foreach (['list', 'index', 'attach', 'detach', 'reorder'] as $key) {
        $routes[$key] = $routes[$key] ?? '';
    }
@endphp

<div
    data-gallery-manager
    data-gm-id="{{ $id }}"
    data-url-list="{{ $routes['list'] }}"
    data-url-index="{{ $routes['index'] }}"
    data-url-attach="{{ $routes['attach'] }}"
    data-url-detach="{{ $routes['detach'] }}"
    data-url-reorder="{{ $routes['reorder'] }}"
    class="grid gap-5 lg:gap-7.5"
>
    <div class="kt-card">
        <div class="kt-card-header py-4">
            <div>
                <h3 class="kt-card-title">{{ $title }}</h3>
                <div class="text-sm text-muted-foreground">İçeriğe bağlı galerileri ana alan ve yan alan olarak yönetin.</div>
            </div>

            <div class="kt-card-toolbar flex items-center gap-2">
                <button
                    type="button"
                    class="kt-btn kt-btn-sm kt-btn-light"
                    data-gm="attach-btn"
                    data-kt-modal-toggle="#{{ $id }}-pickerModal"
                >
                    <i class="ki-outline ki-plus"></i> Ekle
                </button>
            </div>
        </div>

        <div class="kt-card-content p-5 grid gap-6">
            <div data-gm="empty" class="hidden rounded-2xl border border-dashed border-border bg-background/75 px-4 py-4 text-sm text-muted-foreground">
                Henüz galeri eklenmemiş.
            </div>

            <div class="grid grid-cols-1 gap-7">
                <div class="grid gap-3">
                    <div class="font-medium text-foreground">{{ $slots['main'] ?? 'Ana Alan' }}</div>
                    <div data-gm="slot-main" class="grid gap-3" data-slot="main"></div>
                </div>
                <div class="h-px bg-border"></div>
                <div class="grid gap-3">
                    <div class="font-medium text-foreground">{{ $slots['sidebar'] ?? 'Yan Alan' }}</div>
                    <div data-gm="slot-sidebar" class="grid gap-3" data-slot="sidebar"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="kt-modal kt-modal-center" id="{{ $id }}-pickerModal" data-kt-modal="true">
        <div class="kt-modal-content max-w-3xl" style="max-height: 92vh">
            <div class="kt-modal-header">
                <h3 class="kt-modal-title">Galeri Seç</h3>
                <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" data-kt-modal-dismiss="true">
                    <i class="ki-outline ki-cross"></i>
                </button>
            </div>

            <div class="kt-modal-body p-6 grid gap-4 overflow-hidden">
                <div class="flex flex-col gap-3 md:flex-row md:items-center">
                    <input
                        type="text"
                        class="kt-input grow"
                        placeholder="Galeri ara..."
                        data-gm="picker-search"
                    >

                    <select class="kt-select w-full md:w-56" data-gm="picker-slot" data-kt-select="true" data-kt-select-placeholder="Galeri konumu">
                        @foreach($slots as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>

                    <button type="button" class="kt-btn kt-btn-light" data-gm="picker-refresh">
                        <i class="ki-outline ki-arrows-circle"></i> Yenile
                    </button>
                </div>

                <div data-gm="picker-info" class="text-xs text-muted-foreground"></div>

                <div class="kt-card">
                    <div class="kt-card-content p-0">
                        <div data-gm="picker-empty" class="hidden p-4 text-sm text-muted-foreground">
                            Kayıt yok.
                        </div>

                        <div data-gm="picker-list" class="grid divide-y divide-border"></div>

                        <div class="p-4 flex items-center justify-center" data-gm="picker-pagination"></div>
                    </div>
                </div>
            </div>

            <div class="kt-modal-footer justify-end gap-2">
                <button type="button" class="kt-btn kt-btn-light" data-kt-modal-dismiss="true">Kapat</button>
            </div>
        </div>
    </div>
</div>
