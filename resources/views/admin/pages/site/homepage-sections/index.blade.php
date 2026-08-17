@extends('admin.layouts.main.app')

@section('content')
    @php
        $sectionFields = [
            ['name' => 'eyebrow', 'label' => 'Üst Etiket', 'placeholder' => 'Örn. Neden Biz?'],
            ['name' => 'title', 'label' => 'Bölüm Başlığı', 'placeholder' => 'Bölümün güçlü ana mesajı'],
            ['name' => 'description', 'type' => 'textarea', 'rows' => 3, 'label' => 'Bölüm Açıklaması', 'placeholder' => 'Kart grubunu açıklayan kısa metin'],
        ];
        $itemFields = [
            ['name' => 'title', 'label' => 'Kart Başlığı', 'placeholder' => 'Örn. Müşteri Memnuniyeti'],
            ['name' => 'description', 'type' => 'textarea', 'rows' => 3, 'label' => 'Kart Açıklaması', 'placeholder' => 'Ziyaretçiye sunulan değeri kısa ve net anlatın'],
            ['name' => 'link_label', 'label' => 'Bağlantı Metni', 'placeholder' => 'Örn. Detayları İncele'],
        ];
    @endphp

    <div
        class="kt-container-fixed max-w-[96%] grid gap-6"
        data-page="site.homepage-sections.index"
        data-section-reorder-url="{{ route('admin.site.homepage-sections.reorder') }}"
    >
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <span class="kt-badge kt-badge-sm kt-badge-light-primary w-fit">Ana Sayfa İçerik Sistemi</span>
                <div>
                    <h1 class="text-xl font-semibold">Ana Sayfa Bölümleri</h1>
                    <div class="max-w-3xl text-sm text-muted-foreground">
                        Müşteri memnuniyeti, esnek çalışma ve benzeri değer kartlarını çok dilli olarak yönetin; görünümü ve sıralamayı kod değiştirmeden belirleyin.
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.site.homepage.edit') }}" class="kt-btn kt-btn-light">
                    <i class="ki-filled ki-setting-2"></i>
                    Hero Ayarları
                </a>
                <a href="{{ route('site.home') }}" target="_blank" rel="noopener" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-eye"></i>
                    Canlı Ön İzleme
                </a>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Toplam Bölüm</div>
                <div class="mt-2 text-3xl font-semibold">{{ $stats['sections'] }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Yayındaki Bölüm</div>
                <div class="mt-2 text-3xl font-semibold text-success">{{ $stats['active_sections'] }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Toplam Kart</div>
                <div class="mt-2 text-3xl font-semibold text-primary">{{ $stats['items'] }}</div>
            </div>
            <div class="rounded-3xl app-stat-card p-5">
                <div class="text-sm text-muted-foreground">Yayındaki Kart</div>
                <div class="mt-2 text-3xl font-semibold text-warning">{{ $stats['active_items'] }}</div>
            </div>
        </div>

        <section class="kt-card overflow-hidden">
            <div class="kt-card-header py-5">
                <div>
                    <h2 class="kt-card-title">Yeni Değer Kartları Bölümü</h2>
                    <div class="text-sm text-muted-foreground">Aynı ana sayfada birden fazla kart grubu oluşturabilirsiniz.</div>
                </div>
                <span class="kt-badge kt-badge-light-info">Tip: Özellik Kartları</span>
            </div>

            <form method="POST" action="{{ route('admin.site.homepage-sections.store') }}" class="kt-card-content grid gap-5 p-5 lg:p-6" data-native-submit="true">
                @csrf

                @include('admin.components.localized-content-tabs', [
                    'moduleKey' => 'homepage_section_create',
                    'title' => 'Bölüm Metinleri',
                    'description' => 'Varsayılan dili ve aktif diğer dilleri aynı ekrandan doldurun.',
                    'defaultValues' => [
                        'eyebrow' => old('eyebrow'),
                        'title' => old('title'),
                        'description' => old('description'),
                    ],
                    'storedTranslations' => old('translations', []),
                    'fields' => $sectionFields,
                ])

                <div class="grid gap-4 rounded-3xl app-surface-card app-surface-card--soft p-5 md:grid-cols-2 xl:grid-cols-4">
                    <div class="grid gap-2">
                        <label class="kt-form-label">Kolon Sayısı</label>
                        <select name="settings[columns]" class="kt-select" data-kt-select="true">
                            @foreach([2, 3, 4] as $column)
                                <option value="{{ $column }}" @selected((int) old('settings.columns', 3) === $column)>{{ $column }} kolon</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid gap-2">
                        <label class="kt-form-label">Metin Hizası</label>
                        <select name="settings[alignment]" class="kt-select" data-kt-select="true">
                            @foreach($alignmentOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('settings.alignment', 'left') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid gap-2">
                        <label class="kt-form-label">Zemin Teması</label>
                        <select name="settings[surface]" class="kt-select" data-kt-select="true">
                            @foreach($surfaceOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('settings.surface', 'tint') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid gap-2">
                        <label class="kt-form-label">Vurgu Rengi</label>
                        <div class="flex items-center gap-2">
                            <input type="color" name="settings[accent_color]" value="{{ old('settings.accent_color', '#ec6367') }}" class="h-10 w-14 cursor-pointer rounded-xl border border-border bg-transparent p-1">
                            <span class="text-xs text-muted-foreground">İkon ve detaylar</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <label class="flex items-center gap-3">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="kt-checkbox" @checked(old('is_active', true))>
                        <span class="text-sm text-muted-foreground">Bölümü yayında oluştur</span>
                    </label>
                    <button type="submit" class="kt-btn kt-btn-primary">
                        <i class="ki-filled ki-plus"></i>
                        Bölüm Oluştur
                    </button>
                </div>
            </form>
        </section>

        <div id="homepageSectionSortable" class="grid gap-6">
            @forelse($sections as $section)
                @php
                    $sectionSettings = array_replace([
                        'columns' => 3,
                        'alignment' => 'left',
                        'surface' => 'tint',
                        'accent_color' => '#ec6367',
                    ], $section->settings ?? []);
                    $sectionTranslations = $section->translations
                        ->mapWithKeys(fn ($translation) => [$translation->locale => [
                            'eyebrow' => $translation->eyebrow,
                            'title' => $translation->title,
                            'description' => $translation->description,
                        ]])
                        ->all();
                @endphp

                <section id="section-{{ $section->id }}" class="kt-card overflow-hidden scroll-mt-24" data-section-id="{{ $section->id }}">
                    <div class="kt-card-header flex-wrap gap-3 py-5">
                        <div class="flex min-w-0 items-center gap-3">
                            <button type="button" class="kt-btn kt-btn-sm kt-btn-light cursor-move js-section-sort-handle" title="Bölümü sürükleyerek sırala">
                                <i class="ki-outline ki-menu"></i>
                            </button>
                            <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-primary-light text-primary">
                                <i class="ki-filled ki-grid text-xl"></i>
                            </span>
                            <div class="min-w-0">
                                <h2 class="truncate font-semibold">{{ $section->title }}</h2>
                                <div class="text-sm text-muted-foreground">
                                    {{ $section->items->count() }} kart · {{ $sectionSettings['columns'] }} kolon · {{ $surfaceOptions[$sectionSettings['surface']] ?? 'Yumuşak renkli' }}
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="kt-badge {{ $section->is_active ? 'kt-badge-light-success' : 'kt-badge-light' }}">
                                {{ $section->is_active ? 'Yayında' : 'Taslak' }}
                            </span>
                            <form method="POST" action="{{ route('admin.site.homepage-sections.destroy', $section) }}" data-confirm-delete="section">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="kt-btn kt-btn-sm kt-btn-light-danger" title="Bölümü sil">
                                    <i class="ki-filled ki-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="kt-card-content grid gap-6 p-5 lg:p-6">
                        <details class="group rounded-3xl app-surface-card" open>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 p-5">
                                <div>
                                    <div class="font-semibold">Bölüm Ayarları</div>
                                    <div class="text-sm text-muted-foreground">Başlık, görünüm ve yayın durumunu düzenleyin.</div>
                                </div>
                                <i class="ki-filled ki-down text-muted-foreground transition-transform group-open:rotate-180"></i>
                            </summary>

                            <form method="POST" action="{{ route('admin.site.homepage-sections.update', $section) }}" class="grid gap-5 border-t border-border p-5" data-native-submit="true">
                                @csrf
                                @method('PUT')

                                @include('admin.components.localized-content-tabs', [
                                    'moduleKey' => 'homepage_section_' . $section->id,
                                    'title' => 'Bölüm Metinleri',
                                    'description' => 'Bölüm başlığının tüm dil karşılıklarını yönetin.',
                                    'defaultValues' => [
                                        'eyebrow' => $section->eyebrow,
                                        'title' => $section->title,
                                        'description' => $section->description,
                                    ],
                                    'storedTranslations' => $sectionTranslations,
                                    'fields' => $sectionFields,
                                ])

                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                    <div class="grid gap-2">
                                        <label class="kt-form-label">Kolon Sayısı</label>
                                        <select name="settings[columns]" class="kt-select" data-kt-select="true">
                                            @foreach([2, 3, 4] as $column)
                                                <option value="{{ $column }}" @selected((int) $sectionSettings['columns'] === $column)>{{ $column }} kolon</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="grid gap-2">
                                        <label class="kt-form-label">Metin Hizası</label>
                                        <select name="settings[alignment]" class="kt-select" data-kt-select="true">
                                            @foreach($alignmentOptions as $value => $label)
                                                <option value="{{ $value }}" @selected($sectionSettings['alignment'] === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="grid gap-2">
                                        <label class="kt-form-label">Zemin Teması</label>
                                        <select name="settings[surface]" class="kt-select" data-kt-select="true">
                                            @foreach($surfaceOptions as $value => $label)
                                                <option value="{{ $value }}" @selected($sectionSettings['surface'] === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="grid gap-2">
                                        <label class="kt-form-label">Vurgu Rengi</label>
                                        <input type="color" name="settings[accent_color]" value="{{ $sectionSettings['accent_color'] }}" class="h-10 w-full cursor-pointer rounded-xl border border-border bg-transparent p-1">
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <label class="flex items-center gap-3">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1" class="kt-checkbox" @checked($section->is_active)>
                                        <span class="text-sm text-muted-foreground">Bölümü ana sayfada göster</span>
                                    </label>
                                    <button type="submit" class="kt-btn kt-btn-light-primary">Bölümü Güncelle</button>
                                </div>
                            </form>
                        </details>

                        <div class="grid gap-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="font-semibold">İçerik Kartları</h3>
                                    <div class="text-sm text-muted-foreground">Kartları sürükleyerek ziyaretçiye gösterilecek sırayı değiştirin.</div>
                                </div>
                                <span class="kt-badge kt-badge-light-primary w-fit">{{ $section->items->where('is_active', true)->count() }} aktif</span>
                            </div>

                            <details class="group rounded-3xl border border-dashed admin-panel admin-panel--create" data-admin-panel="create">
                                <summary class="admin-panel__header flex cursor-pointer list-none items-center justify-between gap-3 p-5">
                                    <div class="flex items-center gap-3">
                                        <span class="grid size-10 place-items-center rounded-2xl bg-primary text-primary-foreground">
                                            <i class="ki-filled ki-plus"></i>
                                        </span>
                                        <div>
                                            <div class="font-semibold">Bu Bölüme Yeni Kart Ekle</div>
                                            <div class="text-sm text-muted-foreground">Başlık, açıklama, ikon ve isteğe bağlı bağlantı ekleyin.</div>
                                        </div>
                                    </div>
                                    <i class="ki-filled ki-down text-primary transition-transform group-open:rotate-180"></i>
                                </summary>

                                <form method="POST" action="{{ route('admin.site.homepage-sections.items.store', $section) }}" class="admin-panel__content grid gap-5 border-t border-primary/20 p-5" data-native-submit="true">
                                    @csrf

                                    @include('admin.components.localized-content-tabs', [
                                        'moduleKey' => 'homepage_section_item_create_' . $section->id,
                                        'title' => 'Yeni Kart Metinleri',
                                        'description' => 'Kart içeriğinin dil karşılıklarını girin.',
                                        'defaultValues' => ['title' => '', 'description' => '', 'link_label' => ''],
                                        'storedTranslations' => [],
                                        'fields' => $itemFields,
                                    ])

                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div class="grid gap-2">
                                            <label class="kt-form-label">İkon</label>
                                            <select name="icon" class="kt-select" data-kt-select="true">
                                                @foreach($iconOptions as $value => $label)
                                                    <option value="{{ $value }}" @selected($value === 'sparkles')>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="grid gap-2">
                                            <label class="kt-form-label">Bağlantı Adresi</label>
                                            <input name="link_url" class="kt-input" placeholder="/iletisim veya https://...">
                                            <span class="text-xs text-muted-foreground">Bağlantı metni girildiyse bu alan da zorunludur.</span>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <label class="flex items-center gap-3">
                                            <input type="hidden" name="is_active" value="0">
                                            <input type="checkbox" name="is_active" value="1" class="kt-checkbox" checked>
                                            <span class="text-sm text-muted-foreground">Kartı yayında oluştur</span>
                                        </label>
                                        <button type="submit" class="kt-btn kt-btn-primary">Kartı Ekle</button>
                                    </div>
                                </form>
                            </details>

                            <div
                                class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3"
                                data-homepage-item-sortable
                                data-reorder-url="{{ route('admin.site.homepage-sections.items.reorder', $section) }}"
                            >
                                @forelse($section->items as $item)
                                    @php
                                        $itemTranslations = $item->translations
                                            ->mapWithKeys(fn ($translation) => [$translation->locale => [
                                                'title' => $translation->title,
                                                'description' => $translation->description,
                                                'link_label' => $translation->link_label,
                                            ]])
                                            ->all();
                                    @endphp

                                    <article class="flex min-w-0 flex-col rounded-3xl app-surface-card" data-item-id="{{ $item->id }}">
                                        <div class="flex items-start justify-between gap-3 border-b border-border p-4">
                                            <div class="flex min-w-0 items-center gap-3">
                                                <button type="button" class="kt-btn kt-btn-sm kt-btn-light cursor-move js-item-sort-handle" title="Kartı sürükleyerek sırala">
                                                    <i class="ki-outline ki-menu"></i>
                                                </button>
                                                <div class="min-w-0">
                                                    <div class="truncate font-semibold">{{ $item->title }}</div>
                                                    <span class="text-xs {{ $item->is_active ? 'text-success' : 'text-muted-foreground' }}">
                                                        {{ $item->is_active ? 'Yayında' : 'Gizli' }}
                                                    </span>
                                                </div>
                                            </div>
                                            <form method="POST" action="{{ route('admin.site.homepage-sections.items.destroy', [$section, $item]) }}" data-confirm-delete="item">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="kt-btn kt-btn-sm kt-btn-light-danger" title="Kartı sil">
                                                    <i class="ki-filled ki-trash"></i>
                                                </button>
                                            </form>
                                        </div>

                                        <details class="group flex-1">
                                            <summary class="flex cursor-pointer list-none items-center justify-between gap-2 p-4 text-sm font-medium text-primary">
                                                Kartı Düzenle
                                                <i class="ki-filled ki-down transition-transform group-open:rotate-180"></i>
                                            </summary>
                                            <form method="POST" action="{{ route('admin.site.homepage-sections.items.update', [$section, $item]) }}" class="grid gap-4 border-t border-border p-4" data-native-submit="true">
                                                @csrf
                                                @method('PUT')

                                                @include('admin.components.localized-content-tabs', [
                                                    'moduleKey' => 'homepage_section_item_' . $item->id,
                                                    'title' => 'Kart Metinleri',
                                                    'description' => 'Bu kartın tüm dil karşılıklarını yönetin.',
                                                    'defaultValues' => [
                                                        'title' => $item->title,
                                                        'description' => $item->description,
                                                        'link_label' => $item->link_label,
                                                    ],
                                                    'storedTranslations' => $itemTranslations,
                                                    'fields' => $itemFields,
                                                ])

                                                <div class="grid gap-3">
                                                    <div class="grid gap-2">
                                                        <label class="kt-form-label">İkon</label>
                                                        <select name="icon" class="kt-select" data-kt-select="true">
                                                            @foreach($iconOptions as $value => $label)
                                                                <option value="{{ $value }}" @selected($item->icon === $value)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="grid gap-2">
                                                        <label class="kt-form-label">Bağlantı Adresi</label>
                                                        <input name="link_url" class="kt-input" value="{{ $item->link_url }}" placeholder="/iletisim veya https://...">
                                                    </div>
                                                </div>

                                                <label class="flex items-center gap-3">
                                                    <input type="hidden" name="is_active" value="0">
                                                    <input type="checkbox" name="is_active" value="1" class="kt-checkbox" @checked($item->is_active)>
                                                    <span class="text-sm text-muted-foreground">Kartı ana sayfada göster</span>
                                                </label>

                                                <button type="submit" class="kt-btn kt-btn-light-primary w-full">Kartı Güncelle</button>
                                            </form>
                                        </details>
                                    </article>
                                @empty
                                    <div class="rounded-3xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground md:col-span-2 2xl:col-span-3">
                                        Bu bölüm henüz boş. Yukarıdaki formdan ilk kartı ekleyin.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </section>
            @empty
                <div class="kt-card p-10 text-center">
                    <div class="mx-auto grid size-16 place-items-center rounded-3xl bg-primary-light text-primary">
                        <i class="ki-filled ki-grid text-3xl"></i>
                    </div>
                    <h2 class="mt-5 text-lg font-semibold">Henüz ana sayfa bölümü yok</h2>
                    <p class="mt-2 text-sm text-muted-foreground">Yukarıdaki formdan ilk değer kartları bölümünüzü oluşturun.</p>
                </div>
            @endforelse
        </div>
    </div>
@endsection
