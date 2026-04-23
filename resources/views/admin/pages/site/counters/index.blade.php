@extends('admin.layouts.main.app')

@section('content')
    @php
        $extraLanguages = collect($siteLanguages ?? [])->where('code', '!=', $siteDefaultLocale)->values();
    @endphp

    <div
        class="kt-container-fixed max-w-[96%] grid gap-6"
        data-page="site.counters.index"
        data-reorder-url="{{ route('admin.site.counters.reorder') }}"
    >
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <span class="kt-badge kt-badge-sm kt-badge-light-primary w-fit">Site Yönetimi</span>
                <div>
                    <h1 class="text-xl font-semibold">Dinamik Sayaçlar</h1>
                    <div class="text-sm text-muted-foreground">
                        Mutlu müşteri, teslim edilen proje ve benzeri metrikleri içerik sayfalarına bağla, dil varyantlarını aynı kartta yönet.
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl app-stat-card p-5"><div class="text-sm text-muted-foreground">Toplam</div><div class="mt-2 text-3xl font-semibold">{{ $stats['all'] ?? 0 }}</div></div>
            <div class="rounded-3xl app-stat-card p-5"><div class="text-sm text-muted-foreground">Aktif</div><div class="mt-2 text-3xl font-semibold text-success">{{ $stats['active'] ?? 0 }}</div></div>
            <div class="rounded-3xl app-stat-card p-5"><div class="text-sm text-muted-foreground">Global</div><div class="mt-2 text-3xl font-semibold text-primary">{{ $stats['global'] ?? 0 }}</div></div>
            <div class="rounded-3xl app-stat-card p-5"><div class="text-sm text-muted-foreground">Sayfaya Bağlı</div><div class="mt-2 text-3xl font-semibold text-warning">{{ $stats['linked'] ?? 0 }}</div></div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[400px_minmax(0,1fr)]">
            <div class="kt-card">
                <div class="kt-card-header py-5">
                    <div>
                        <h3 class="kt-card-title">Yeni Sayaç Ekle</h3>
                        <div class="text-sm text-muted-foreground">Karttaki sayı, etiket ve kısa açıklamayı belirle.</div>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.site.counters.store') }}" class="kt-card-content grid gap-4 p-6">
                    @csrf

                    <div class="grid gap-2">
                        <label class="kt-form-label">Bağlı Sayfa</label>
                        <select name="site_page_id" class="kt-select" data-kt-select="true">
                            <option value="">Global Sayaç</option>
                            @foreach($pages as $page)
                                <option value="{{ $page->id }}" @selected((int) old('site_page_id') === (int) $page->id)>{{ $page->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-3">
                        <div class="grid gap-2 lg:col-span-2">
                            <label class="kt-form-label">Değer</label>
                            <input type="number" name="value" class="kt-input" min="0" value="{{ old('value', 0) }}">
                        </div>
                        <div class="grid gap-2 lg:col-span-1">
                            <label class="kt-form-label">İkon</label>
                            <input name="icon_class" class="kt-input" value="{{ old('icon_class', 'ki-filled ki-chart-simple') }}">
                        </div>
                    </div>

                    @include('admin.components.localized-content-tabs', [
                        'moduleKey' => 'site_counter_create',
                        'title' => 'Sayaç Dil Sekmeleri',
                        'description' => 'Sayaç etiketini ve açıklamasını tüm diller için aynı panelden yönet.',
                        'defaultValues' => [
                            'label' => old('label'),
                            'prefix' => old('prefix'),
                            'suffix' => old('suffix'),
                            'description' => old('description'),
                        ],
                        'storedTranslations' => old('translations', []),
                        'fields' => [
                            ['name' => 'label', 'label' => 'Etiket', 'placeholder' => 'Örn. Mutlu Müşteri'],
                            ['name' => 'prefix', 'label' => 'Önek', 'placeholder' => '+'],
                            ['name' => 'suffix', 'label' => 'Sonek', 'placeholder' => '+'],
                            ['name' => 'description', 'type' => 'textarea', 'rows' => 3, 'label' => 'Açıklama', 'placeholder' => 'Sayaç kartının alt açıklaması'],
                        ],
                    ])

                    <label class="flex items-start gap-3 rounded-2xl app-surface-card app-surface-card--soft p-4">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="kt-checkbox mt-1" @checked(old('is_active', true))>
                        <span>
                            <span class="block font-medium text-foreground">Aktif olarak kaydet</span>
                            <span class="text-sm text-muted-foreground">Kapatırsan ön yüzde görünmez.</span>
                        </span>
                    </label>

                    <button type="submit" class="kt-btn kt-btn-primary">Sayaç Kaydet</button>
                </form>
            </div>

            <div class="kt-card">
                <div class="kt-card-header py-5">
                    <div>
                        <h3 class="kt-card-title">Sayaç Havuzu</h3>
                        <div class="text-sm text-muted-foreground">Sıralamayı sürükleyerek değiştir, metinleri kart üstünde güncelle.</div>
                    </div>
                </div>

                <div id="siteCounterSortable" class="kt-card-content grid gap-4 p-6">
                    @foreach($counters as $counter)
                        <div class="rounded-[28px] app-surface-card p-5" data-id="{{ $counter->id }}">
                            <div class="mb-4 flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <button type="button" class="kt-btn kt-btn-sm kt-btn-light cursor-move js-sort-handle">
                                        <i class="ki-outline ki-menu"></i>
                                    </button>
                                    <div>
                                        <div class="font-semibold">{{ $counter->displayValue() }} • {{ $counter->label }}</div>
                                        <div class="text-sm text-muted-foreground">{{ $counter->page?->title ?: 'Global Sayaç' }}</div>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('admin.site.counters.destroy', $counter) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="kt-btn kt-btn-sm kt-btn-danger" onclick="return confirm('Bu sayaç silinsin mi?')">
                                        Sil
                                    </button>
                                </form>
                            </div>

                            <form method="POST" action="{{ route('admin.site.counters.update', $counter) }}" class="grid gap-4">
                                @csrf
                                @method('PUT')

                                @php
                                    $counterTranslations = collect($counter->translations)
                                        ->mapWithKeys(fn ($translation) => [
                                            $translation->locale => [
                                                'label' => $translation->label,
                                                'prefix' => $translation->prefix,
                                                'suffix' => $translation->suffix,
                                                'description' => $translation->description,
                                            ],
                                        ])
                                        ->toArray();
                                @endphp

                                <div class="grid gap-4 lg:grid-cols-2">
                                    <div class="grid gap-2">
                                        <label class="kt-form-label">Bağlı Sayfa</label>
                                        <select name="site_page_id" class="kt-select" data-kt-select="true">
                                            <option value="">Global Sayaç</option>
                                            @foreach($pages as $page)
                                                <option value="{{ $page->id }}" @selected((int) $counter->site_page_id === (int) $page->id)>{{ $page->title }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="grid gap-4 lg:grid-cols-3">
                                    <div class="grid gap-2 lg:col-span-2">
                                        <label class="kt-form-label">Değer</label>
                                        <input type="number" name="value" class="kt-input" min="0" value="{{ $counter->value }}">
                                    </div>
                                    <div class="grid gap-2">
                                        <label class="kt-form-label">İkon</label>
                                        <input name="icon_class" class="kt-input" value="{{ $counter->icon_class }}">
                                    </div>
                                </div>

                                @include('admin.components.localized-content-tabs', [
                                    'moduleKey' => 'site_counter_' . $counter->id,
                                    'title' => 'Sayaç Dil Sekmeleri',
                                    'description' => 'Bu sayaç kartının tüm dil karşılıklarını sekmeler üzerinden güncelle.',
                                    'defaultValues' => [
                                        'label' => $counter->label,
                                        'prefix' => $counter->prefix,
                                        'suffix' => $counter->suffix,
                                        'description' => $counter->description,
                                    ],
                                    'storedTranslations' => $counterTranslations,
                                    'fields' => [
                                        ['name' => 'label', 'label' => 'Etiket'],
                                        ['name' => 'prefix', 'label' => 'Önek'],
                                        ['name' => 'suffix', 'label' => 'Sonek'],
                                        ['name' => 'description', 'type' => 'textarea', 'rows' => 3, 'label' => 'Açıklama'],
                                    ],
                                ])

                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <label class="flex items-center gap-3">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1" class="kt-checkbox" @checked($counter->is_active)>
                                        <span class="text-sm text-muted-foreground">Aktif göster</span>
                                    </label>

                                    <button type="submit" class="kt-btn kt-btn-light-primary">Güncelle</button>
                                </div>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
