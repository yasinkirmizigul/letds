@php
    $category = $category ?? null;
    $isEdit = filled($category?->id);
    $storedTranslations = old('translations');

    if (!is_array($storedTranslations)) {
        $storedTranslations = collect($category?->translations ?? [])
            ->mapWithKeys(fn ($translation) => [
                $translation->locale => [
                    'name' => $translation->name,
                    'slug' => $translation->slug,
                    'description' => $translation->description,
                ],
            ])
            ->toArray();
    }
@endphp

<div class="grid gap-6">
    @include('admin.components.localized-content-tabs', [
        'moduleKey' => 'category',
        'title' => 'Kategori Dilleri',
        'description' => 'Varsayılan dil ve ek diller için kategori adı, slug ve açıklama alanlarını sekmelerden yönetin.',
        'urlBase' => url('/kategori'),
        'defaultValues' => [
            'name' => old('name', $category->name ?? ''),
            'slug' => old('slug', $category->slug ?? ''),
            'description' => old('description', $category->description ?? ''),
        ],
        'storedTranslations' => $storedTranslations,
        'fields' => [
            ['name' => 'name', 'id' => 'cat_name', 'label' => 'Kategori Adı', 'placeholder' => 'Kategori adını yazın', 'slug_source' => true],
            ['name' => 'slug', 'id' => 'cat_slug', 'type' => 'slug', 'label' => 'Slug'],
            ['name' => 'description', 'type' => 'textarea', 'rows' => 4, 'label' => 'Açıklama'],
        ],
    ])

    <div class="kt-card">
        <div class="kt-card-header py-5">
            <div>
                <h3 class="kt-card-title">Kategori Ayarları</h3>
                <div class="text-sm text-muted-foreground">Üst kategori ve aktiflik ayarlarını yönetin.</div>
            </div>
        </div>
        <div class="kt-card-content grid gap-5 p-6">
            <div class="grid gap-2">
                <label class="kt-form-label">Üst Kategori</label>
                <select name="parent_id" class="kt-select" data-kt-select="true" data-kt-select-placeholder="Üst kategori seçin">
                    <option value="">Yok</option>
                    @foreach($parentOptions ?? [] as $option)
                        <option value="{{ $option['id'] }}" @selected((string) old('parent_id', $category->parent_id ?? '') === (string) $option['id'])>
                            {{ $option['label'] }}
                        </option>
                    @endforeach
                </select>
                @error('parent_id')<div class="text-xs text-danger">{{ $message }}</div>@enderror
            </div>

            <label class="flex items-start justify-between gap-4 rounded-2xl app-surface-card app-surface-card--soft p-4">
                <span>
                    <span class="block font-medium text-foreground">Aktif</span>
                    <span class="text-sm text-muted-foreground">Pasif kategoriler seçim listelerinde gizlenebilir.</span>
                </span>
                <span class="flex items-center gap-3">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="kt-switch kt-switch-mono" @checked(old('is_active', $category->is_active ?? true))>
                </span>
            </label>
        </div>
    </div>
</div>
