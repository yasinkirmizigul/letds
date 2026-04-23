@extends('admin.layouts.main.app')

@section('content')
    @php
        $extraLanguages = collect($siteLanguages ?? [])->where('code', '!=', $siteDefaultLocale)->values();
    @endphp

    <div
        class="kt-container-fixed max-w-[96%] grid gap-6"
        data-page="site.faqs.index"
        data-reorder-url="{{ route('admin.site.faqs.reorder') }}"
    >
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <span class="kt-badge kt-badge-sm kt-badge-light-primary w-fit">Site Yönetimi</span>
                <div>
                    <h1 class="text-xl font-semibold">Sıkça Sorulan Sorular</h1>
                    <div class="text-sm text-muted-foreground">
                        Global veya sayfa bazlı SSS alanlarını sürükle-bırak sıralama ile yönet, dil karşılıklarını aynı karttan gir.
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
                        <h3 class="kt-card-title">Yeni SSS Ekle</h3>
                        <div class="text-sm text-muted-foreground">İçerik sayfasına bağlayabilir veya global bırakabilirsin.</div>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.site.faqs.store') }}" class="kt-card-content grid gap-4 p-6">
                    @csrf

                    <div class="grid gap-2">
                        <label class="kt-form-label">Bağlı Sayfa</label>
                        <select name="site_page_id" class="kt-select" data-kt-select="true" data-kt-select-placeholder="Global SSS">
                            <option value="">Global SSS</option>
                            @foreach($pages as $page)
                                <option value="{{ $page->id }}" @selected((int) old('site_page_id') === (int) $page->id)>{{ $page->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid gap-2">
                        <label class="kt-form-label">İkon</label>
                        <input name="icon_class" class="kt-input" value="{{ old('icon_class', 'ki-filled ki-questionnaire-tablet') }}" placeholder="ki-filled ki-questionnaire-tablet">
                    </div>

                    @include('admin.components.localized-content-tabs', [
                        'moduleKey' => 'site_faq_create',
                        'title' => 'SSS Dil Sekmeleri',
                        'description' => 'Varsayılan soru-cevap metinlerini ve diğer dil karşılıklarını aynı yapı içinde yönet.',
                        'defaultValues' => [
                            'group_label' => old('group_label'),
                            'question' => old('question'),
                            'answer' => old('answer'),
                        ],
                        'storedTranslations' => old('translations', []),
                        'fields' => [
                            ['name' => 'group_label', 'label' => 'Grup Etiketi', 'placeholder' => 'Örn. Teslimat / Randevu'],
                            ['name' => 'question', 'label' => 'Soru', 'placeholder' => 'En çok sorulan soru başlığı'],
                            ['name' => 'answer', 'type' => 'textarea', 'rows' => 4, 'label' => 'Cevap', 'placeholder' => 'Ziyaretçiye gösterilecek detaylı cevap'],
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

                    <button type="submit" class="kt-btn kt-btn-primary">SSS Kaydet</button>
                </form>
            </div>

            <div class="kt-card">
                <div class="kt-card-header py-5">
                    <div>
                        <h3 class="kt-card-title">SSS Havuzu</h3>
                        <div class="text-sm text-muted-foreground">Kartları sürükleyerek ön yüzdeki sıralamayı güncelleyebilirsin.</div>
                    </div>
                </div>

                <div id="siteFaqSortable" class="kt-card-content grid gap-4 p-6">
                    @foreach($faqs as $faq)
                        <div class="rounded-[28px] app-surface-card p-5" data-id="{{ $faq->id }}">
                            <div class="mb-4 flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <button type="button" class="kt-btn kt-btn-sm kt-btn-light cursor-move js-sort-handle">
                                        <i class="ki-outline ki-menu"></i>
                                    </button>
                                    <div>
                                        <div class="font-semibold">{{ $faq->question }}</div>
                                        <div class="text-sm text-muted-foreground">
                                            {{ $faq->page?->title ?: 'Global SSS' }}
                                            @if($faq->group_label)
                                                • {{ $faq->group_label }}
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('admin.site.faqs.destroy', $faq) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="kt-btn kt-btn-sm kt-btn-danger" onclick="return confirm('Bu SSS kaydı silinsin mi?')">
                                        Sil
                                    </button>
                                </form>
                            </div>

                            <form method="POST" action="{{ route('admin.site.faqs.update', $faq) }}" class="grid gap-4">
                                @csrf
                                @method('PUT')

                                @php
                                    $faqTranslations = collect($faq->translations)
                                        ->mapWithKeys(fn ($translation) => [
                                            $translation->locale => [
                                                'group_label' => $translation->group_label,
                                                'question' => $translation->question,
                                                'answer' => $translation->answer,
                                            ],
                                        ])
                                        ->toArray();
                                @endphp

                                <div class="grid gap-4 lg:grid-cols-2">
                                    <div class="grid gap-2">
                                        <label class="kt-form-label">Bağlı Sayfa</label>
                                        <select name="site_page_id" class="kt-select" data-kt-select="true">
                                            <option value="">Global SSS</option>
                                            @foreach($pages as $page)
                                                <option value="{{ $page->id }}" @selected((int) $faq->site_page_id === (int) $page->id)>{{ $page->title }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="grid gap-2">
                                        <label class="kt-form-label">İkon</label>
                                        <input name="icon_class" class="kt-input" value="{{ $faq->icon_class }}">
                                    </div>
                                </div>

                                @include('admin.components.localized-content-tabs', [
                                    'moduleKey' => 'site_faq_' . $faq->id,
                                    'title' => 'SSS Dil Sekmeleri',
                                    'description' => 'Bu SSS kartının tüm dil varyantlarını sekmelerden güncelle.',
                                    'defaultValues' => [
                                        'group_label' => $faq->group_label,
                                        'question' => $faq->question,
                                        'answer' => $faq->answer,
                                    ],
                                    'storedTranslations' => $faqTranslations,
                                    'fields' => [
                                        ['name' => 'group_label', 'label' => 'Grup Etiketi'],
                                        ['name' => 'question', 'label' => 'Soru'],
                                        ['name' => 'answer', 'type' => 'textarea', 'rows' => 4, 'label' => 'Cevap'],
                                    ],
                                ])

                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <label class="flex items-center gap-3">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1" class="kt-checkbox" @checked($faq->is_active)>
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
