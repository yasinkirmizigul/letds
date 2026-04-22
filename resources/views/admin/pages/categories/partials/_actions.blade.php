<div class="flex items-center justify-end gap-2">
    <a href="{{ route('admin.categories.edit', $c) }}" class="kt-btn kt-btn-sm kt-btn-light">
        <i class="ki-outline ki-pencil"></i>
        Düzenle
    </a>

    @perm('categories.delete')
        <button type="button"
                class="kt-btn kt-btn-sm kt-btn-danger"
                data-delete
                data-url="{{ route('admin.categories.destroy', $c) }}">
            <i class="ki-outline ki-trash"></i>
            Sil
        </button>
    @endperm
</div>
