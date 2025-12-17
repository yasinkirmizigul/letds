@php
    $isEdit = !is_null($category);
@endphp

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <div class="flex flex-col gap-2">
        <label class="kt-form-label font-normal text-mono">Kategori Adı</label>
        <input id="cat_name"
               class="kt-input @error('name') kt-input-invalid @enderror"
               name="name"
               value="{{ old('name', $category->name ?? '') }}"
               required>
        @error('name') <div class="text-xs text-danger">{{ $message }}</div> @enderror
    </div>

    <div class="flex flex-col gap-2">
        <div class="flex items-center justify-between">
            <label class="kt-form-label font-normal text-mono mb-0">Slug</label>

            <label class="inline-flex items-center gap-2 select-none">
                <span class="text-sm text-muted-foreground">Otomatik</span>
                <input id="cat_slug_auto" type="checkbox" class="kt-switch kt-switch-mono" {{ $isEdit ? '' : 'checked' }}>
            </label>
        </div>

        <input id="cat_slug"
               class="kt-input @error('slug') kt-input-invalid @enderror"
               name="slug"
               value="{{ old('slug', $category->slug ?? '') }}"
               required>
        @error('slug') <div class="text-xs text-danger">{{ $message }}</div> @enderror
    </div>

    <div class="lg:col-span-2 flex flex-col gap-2">
        <label class="kt-form-label font-normal text-mono">Açıklama</label>
        <textarea class="kt-input min-h-[120px] @error('description') kt-input-invalid @enderror"
                  name="description">{{ old('description', $category->description ?? '') }}</textarea>
        @error('description') <div class="text-xs text-danger">{{ $message }}</div> @enderror
    </div>

    <div class="lg:col-span-2 flex items-center justify-between border rounded-xl p-4">
        <div class="flex flex-col">
            <span class="font-medium">Aktif</span>
            <span class="text-sm text-muted-foreground">Pasif olan kategori seçim listelerinde gizlenir</span>
        </div>

        <input type="checkbox"
               name="is_active"
               value="1"
               class="kt-switch kt-switch-mono"
            @checked(old('is_active', $category->is_active ?? true))>
    </div>

</div>
