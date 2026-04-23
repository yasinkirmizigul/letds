@php
    $extraLanguages = collect($siteLanguages ?? [])->where('code', '!=', $siteDefaultLocale)->values();
@endphp

<div class="rounded-[28px] app-surface-card p-5 js-navigation-item" data-id="{{ $item->id }}">
    <div class="flex items-start justify-between gap-3">
        <div class="flex items-start gap-3">
            <button type="button" class="kt-btn kt-btn-sm kt-btn-light cursor-move js-navigation-handle">
                <i class="ki-outline ki-menu"></i>
            </button>

            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="font-semibold text-foreground">{{ $item->title }}</div>
                    <span class="kt-badge kt-badge-sm {{ $item->is_active ? 'kt-badge-light-success' : 'kt-badge-light' }}">
                        {{ $item->is_active ? 'Aktif' : 'Pasif' }}
                    </span>
                    <span class="kt-badge kt-badge-sm kt-badge-light">
                        {{ $item->link_type === \App\Models\Site\SiteNavigationItem::LINK_TYPE_PAGE ? 'İçerik' : 'Özel URL' }}
                    </span>
                </div>
                <div class="mt-1 text-sm text-muted-foreground">
                    {{ $item->link_type === \App\Models\Site\SiteNavigationItem::LINK_TYPE_PAGE ? ($item->page?->title ?: 'Sayfa seçilmedi') : ($item->url ?: 'URL tanımlanmadı') }}
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.site.navigation.destroy', $item) }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="kt-btn kt-btn-sm kt-btn-danger" onclick="return confirm('Bu menü öğesi silinsin mi?')">
                Sil
            </button>
        </form>
    </div>

    <details class="mt-4 rounded-3xl app-surface-card app-surface-card--soft p-4">
        <summary class="cursor-pointer list-none text-sm font-medium text-foreground">Detayları düzenle</summary>

        <form method="POST" action="{{ route('admin.site.navigation.update', $item) }}" class="mt-4 grid gap-4">
            @csrf
            @method('PUT')

            <input type="hidden" name="location" value="{{ $item->location }}">
            @php
                $navigationTranslations = collect($item->translations)
                    ->mapWithKeys(fn ($translation) => [
                        $translation->locale => [
                            'title' => $translation->title,
                        ],
                    ])
                    ->toArray();
            @endphp

            <div class="grid gap-4 lg:grid-cols-1">
                <div class="grid gap-2">
                    <label class="kt-form-label">İkon</label>
                    <input name="icon_class" class="kt-input" value="{{ $item->icon_class }}">
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="grid gap-2">
                    <label class="kt-form-label">Bağlantı Türü</label>
                    <select name="link_type" class="kt-select" data-kt-select="true" data-link-type-select="true">
                        @foreach($linkTypeOptions as $value => $label)
                            <option value="{{ $value }}" @selected($item->link_type === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid gap-2">
                    <label class="kt-form-label">Açılış Hedefi</label>
                    <select name="target" class="kt-select" data-kt-select="true">
                        @foreach($targetOptions as $value => $label)
                            <option value="{{ $value }}" @selected($item->target === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid gap-2" data-link-field="page">
                <label class="kt-form-label">İçerik Sayfası</label>
                <select name="site_page_id" class="kt-select" data-kt-select="true">
                    <option value="">Sayfa seç</option>
                    @foreach($pages as $page)
                        <option value="{{ $page->id }}" @selected((int) $item->site_page_id === (int) $page->id)>{{ $page->title }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid gap-2" data-link-field="url">
                <label class="kt-form-label">Özel URL</label>
                <input name="url" class="kt-input" value="{{ $item->url }}" placeholder="https://ornek.com veya /iletisim">
            </div>

            @include('admin.components.localized-content-tabs', [
                'moduleKey' => 'site_navigation_' . $item->id,
                'title' => 'Menü Başlığı Dil Sekmeleri',
                'description' => 'Bu menü öğesinin farklı dillerde görünen başlığını sekmeler üzerinden yönet.',
                'defaultValues' => [
                    'title' => $item->title,
                ],
                'storedTranslations' => $navigationTranslations,
                'fields' => [
                    ['name' => 'title', 'label' => 'Menü Başlığı'],
                ],
            ])

            <div class="flex flex-wrap items-center justify-between gap-3">
                <label class="flex items-center gap-3">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="kt-checkbox" @checked($item->is_active)>
                    <span class="text-sm text-muted-foreground">Aktif göster</span>
                </label>

                <button type="submit" class="kt-btn kt-btn-light-primary">Güncelle</button>
            </div>
        </form>
    </details>

    @unless($isChild)
        <div class="mt-4 grid gap-3 pl-4 js-navigation-list js-navigation-children" data-location="{{ $item->location }}" data-child-list="true">
            @foreach($item->children as $child)
                @include('admin.pages.site.navigation._item', [
                    'item' => $child,
                    'pages' => $pages,
                    'linkTypeOptions' => $linkTypeOptions,
                    'targetOptions' => $targetOptions,
                    'isChild' => true,
                ])
            @endforeach
        </div>
    @endunless
</div>
