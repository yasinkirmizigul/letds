<div
    class="kt-modal kt-modal-center"
    data-kt-modal="true"
    id="search_modal"
    data-admin-quick-search
    data-search-url="{{ route('admin.quick-search') }}"
>
    <div class="kt-modal-content admin-quick-search-modal">
        <div class="kt-modal-header admin-quick-search-modal__header">
            <div class="admin-quick-search-input-wrap">
                <i class="ki-filled ki-magnifier admin-quick-search-input-wrap__icon"></i>
                <input
                    class="kt-input kt-input-ghost admin-quick-search-input"
                    name="query"
                    placeholder="Panelde ürün, sipariş, üye, mesaj, sayfa ara..."
                    type="text"
                    autocomplete="off"
                    data-quick-search-input
                />
                <span class="admin-quick-search-spinner hidden" data-quick-search-spinner></span>
            </div>

            <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0" type="button" data-kt-modal-dismiss="true" data-quick-search-close>
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>

        <div class="kt-modal-body admin-quick-search-modal__body">
            <div class="admin-quick-search-toolbar">
                <div class="flex items-center gap-2">
                    <span class="kt-badge kt-badge-sm kt-badge-light-primary">Quick Search</span>
                    <span class="text-xs text-muted-foreground">En az 2 karakter yaz</span>
                </div>
                <div class="hidden items-center gap-1 text-xs text-muted-foreground sm:flex">
                    <span class="admin-quick-search-kbd">↑</span>
                    <span class="admin-quick-search-kbd">↓</span>
                    <span>gezin</span>
                    <span class="admin-quick-search-kbd">Enter</span>
                    <span>aç</span>
                </div>
            </div>

            <div class="admin-quick-search-state hidden" data-quick-search-error>
                <i class="ki-filled ki-information-2 text-danger"></i>
                <div>
                    <div class="font-semibold text-foreground">Arama yapılamadı</div>
                    <div class="text-sm text-muted-foreground">Bağlantıyı kontrol edip tekrar deneyin.</div>
                </div>
            </div>

            <div class="admin-quick-search-results" data-quick-search-results></div>

            <div class="admin-quick-search-state" data-quick-search-empty>
                <i class="ki-filled ki-magnifier text-primary"></i>
                <div>
                    <div class="font-semibold text-foreground">Aramaya başlamak için yaz</div>
                    <div class="text-sm text-muted-foreground">Ürün SKU'su, sipariş numarası, müşteri e-postası, içerik başlığı veya panel sayfası yazabilirsin.</div>
                </div>
            </div>
        </div>
    </div>
</div>
