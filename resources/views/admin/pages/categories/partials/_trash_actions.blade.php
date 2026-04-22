<div class="flex gap-2 justify-end">
    <button data-restore
            data-url="{{ route('admin.categories.restore', $c->id) }}"
            class="kt-btn kt-btn-sm kt-btn-light">
        Geri Yükle
    </button>

    <button data-force
            data-url="{{ route('admin.categories.forceDestroy', $c->id) }}"
            class="kt-btn kt-btn-sm kt-btn-danger">
        Kalıcı Sil
    </button>
</div>
