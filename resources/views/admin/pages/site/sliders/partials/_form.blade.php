@php
    $theme = old('theme', $slider->theme ?? \App\Models\Site\HomeSlider::THEME_DARK);
    $cropX = old('crop_x', $slider->crop_x ?? 50);
    $cropY = old('crop_y', $slider->crop_y ?? 50);
    $cropZoom = old('crop_zoom', $slider->crop_zoom ?? 1);
    $overlayStrength = old('overlay_strength', $slider->overlay_strength ?? 40);
    $isActive = (bool) old('is_active', $slider->is_active ?? true);
    $imageUrl = $slider?->imageUrl();
    $languages = collect($siteLanguages ?? []);
    $defaultLanguage = $languages->firstWhere('code', $siteDefaultLocale) ?? $languages->first();
    $tabLanguages = $defaultLanguage ? collect([$defaultLanguage])->merge($languages->where('code', '!=', $siteDefaultLocale))->values() : collect();
    $storedTranslations = old('translations');

    if (!is_array($storedTranslations)) {
        $storedTranslations = collect($sliderTranslations ?? [])
            ->mapWithKeys(fn ($translation) => [
                $translation->locale => [
                    'badge' => $translation->badge,
                    'title' => $translation->title,
                    'subtitle' => $translation->subtitle,
                    'body' => $translation->body,
                    'cta_label' => $translation->cta_label,
                    'cta_url' => $translation->cta_url,
                ],
            ])
            ->toArray();
    }
@endphp

<div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.45fr)_420px]">
    <div class="grid gap-6">
        <div class="kt-card">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Slider İçerik Dilleri</h3>
                    <div class="text-sm text-muted-foreground">Varsayılan dil ve ek diller için slider metinlerini aynı seviyedeki sekmelerden yönetin.</div>
                </div>
            </div>
            <div class="kt-card-content p-6">
                @if($tabLanguages->isEmpty())
                    <div class="rounded-3xl border border-dashed border-border px-6 py-10 text-center text-sm text-muted-foreground">
                        Dil kaydı bulunamadı.
                    </div>
                @else
                    <div class="kt-tabs kt-tabs-line mb-6" data-kt-tabs="true">
                        <div class="flex flex-wrap gap-2">
                            @foreach($tabLanguages as $language)
                                @php
                                    $isDefault = $language->code === $siteDefaultLocale;
                                @endphp
                                <button type="button" class="kt-tab-toggle {{ $loop->first ? 'active' : '' }}" data-kt-tab-toggle="#slider_locale_{{ $language->code }}">
                                    {{ $language->native_name }}
                                    @if($isDefault)
                                        <span class="ms-2 kt-badge kt-badge-sm kt-badge-light-primary">Varsayılan</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>

                    @foreach($tabLanguages as $language)
                        @php
                            $isDefault = $language->code === $siteDefaultLocale;
                            $row = $isDefault
                                ? [
                                    'badge' => old('badge', $slider->badge ?? ''),
                                    'title' => old('title', $slider->title ?? ''),
                                    'subtitle' => old('subtitle', $slider->subtitle ?? ''),
                                    'body' => old('body', $slider->body ?? ''),
                                    'cta_label' => old('cta_label', $slider->cta_label ?? ''),
                                    'cta_url' => old('cta_url', $slider->cta_url ?? ''),
                                ]
                                : data_get($storedTranslations, $language->code, []);
                        @endphp

                        <div id="slider_locale_{{ $language->code }}" class="{{ $loop->first ? '' : 'hidden' }}">
                            <div class="rounded-[28px] app-surface-card p-5">
                                <div class="mb-5 flex items-center justify-between gap-3">
                                    <div>
                                        <div class="font-semibold text-foreground">{{ $language->native_name }}</div>
                                        <div class="text-sm text-muted-foreground">{{ $isDefault ? 'Varsayılan slider metni' : $language->code . ' diline özel slider metni' }}</div>
                                    </div>
                                    <span class="kt-badge kt-badge-sm kt-badge-light">{{ $language->code }}</span>
                                </div>

                                <div class="grid gap-4">
                                    <div class="grid gap-4 lg:grid-cols-2">
                                        <div class="grid gap-2">
                                            <label class="kt-form-label">Rozet / Kicker</label>
                                            <input name="{{ $isDefault ? 'badge' : "translations[{$language->code}][badge]" }}" class="kt-input" value="{{ data_get($row, 'badge', '') }}" placeholder="Örn. Premium Çözüm">
                                        </div>
                                        <div class="grid gap-2">
                                            <label class="kt-form-label">Başlık</label>
                                            <input name="{{ $isDefault ? 'title' : "translations[{$language->code}][title]" }}" class="kt-input" value="{{ data_get($row, 'title', '') }}" placeholder="Öne çıkacak ana başlık">
                                        </div>
                                    </div>

                                    <div class="grid gap-2">
                                        <label class="kt-form-label">Alt Başlık</label>
                                        <textarea name="{{ $isDefault ? 'subtitle' : "translations[{$language->code}][subtitle]" }}" rows="3" class="kt-textarea">{{ data_get($row, 'subtitle', '') }}</textarea>
                                    </div>

                                    <div class="grid gap-2">
                                        <label class="kt-form-label">Açıklama</label>
                                        <textarea name="{{ $isDefault ? 'body' : "translations[{$language->code}][body]" }}" rows="5" class="kt-textarea">{{ data_get($row, 'body', '') }}</textarea>
                                    </div>

                                    <div class="grid gap-4 lg:grid-cols-2">
                                        <div class="grid gap-2">
                                            <label class="kt-form-label">CTA Metni</label>
                                            <input name="{{ $isDefault ? 'cta_label' : "translations[{$language->code}][cta_label]" }}" class="kt-input" value="{{ data_get($row, 'cta_label', '') }}" placeholder="Örn. Teklif Al">
                                        </div>
                                        <div class="grid gap-2">
                                            <label class="kt-form-label">CTA URL</label>
                                            <input name="{{ $isDefault ? 'cta_url' : "translations[{$language->code}][cta_url]" }}" class="kt-input" value="{{ data_get($row, 'cta_url', '') }}" placeholder="/iletisim veya https://...">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        <div class="kt-card">
            <div class="kt-card-header py-5">
                <div>
                    <h3 class="kt-card-title">Çerçeve ve Tema</h3>
                    <div class="text-sm text-muted-foreground">Kadraj kontrolü, tema ve görünürlük ayarları.</div>
                </div>
            </div>
            <div class="kt-card-content grid gap-5 p-6">
                <div class="grid gap-2">
                    <label class="kt-form-label">Tema</label>
                    <select name="theme" class="kt-select" data-kt-select="true">
                        @foreach($themeOptions as $value => $label)
                            <option value="{{ $value }}" @selected($theme === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="grid gap-2">
                        <label class="kt-form-label">Odak X</label>
                        <input type="range" min="0" max="100" step="1" name="crop_x" value="{{ $cropX }}" data-slider-preview-input="crop-x">
                    </div>
                    <div class="grid gap-2">
                        <label class="kt-form-label">Odak Y</label>
                        <input type="range" min="0" max="100" step="1" name="crop_y" value="{{ $cropY }}" data-slider-preview-input="crop-y">
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="grid gap-2">
                        <label class="kt-form-label">Zoom</label>
                        <input type="range" min="1" max="2.5" step="0.05" name="crop_zoom" value="{{ $cropZoom }}" data-slider-preview-input="crop-zoom">
                    </div>
                    <div class="grid gap-2">
                        <label class="kt-form-label">Overlay Gücü</label>
                        <input type="range" min="0" max="90" step="1" name="overlay_strength" value="{{ $overlayStrength }}" data-slider-preview-input="overlay-strength">
                    </div>
                </div>

                <label class="flex items-start gap-3 rounded-2xl app-surface-card app-surface-card--soft p-4">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="kt-checkbox mt-1" @checked($isActive)>
                    <span>
                        <span class="block font-medium text-foreground">Aktif olarak kullan</span>
                        <span class="text-sm text-muted-foreground">Pasif kayıtlar sahnede gösterilmez.</span>
                    </span>
                </label>
            </div>
        </div>
    </div>

    <div class="grid gap-6 self-start xl:sticky xl:top-6">
        @include('admin.components.featured-image-manager', [
            'title' => 'Slider Görseli',
            'hint' => 'Görseli yükle veya medya kütüphanesinden seç. Kadrajı canlı önizlemede kontrol edebilirsin.',
            'fileName' => 'image',
            'mediaIdName' => 'image_media_id',
            'clearFlagName' => 'clear_image',
            'currentMediaId' => old('image_media_id', $slider->image_media_id ?? null),
            'currentUrl' => $imageUrl,
        ])

        <div class="rounded-[28px] app-surface-card overflow-hidden" data-slider-preview-card="true">
            <div class="relative h-[420px]">
                <div class="absolute inset-0 bg-slate-950/40" data-slider-preview-overlay="true"></div>
                <img
                    src="{{ $imageUrl }}"
                    alt=""
                    class="absolute inset-0 h-full w-full object-cover"
                    data-slider-preview-image="true"
                    style="object-position: {{ $cropX }}% {{ $cropY }}%; transform: scale({{ $cropZoom }});"
                >
                <div class="absolute inset-0 z-10 flex flex-col justify-end p-6 text-white">
                    <div class="mb-3 inline-flex w-fit rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs uppercase tracking-[0.24em]" data-slider-preview-badge="true">
                        {{ old('badge', $slider->badge ?? '') ?: 'Rozet Alanı' }}
                    </div>
                    <div class="text-3xl font-semibold leading-tight" data-slider-preview-title="true">{{ old('title', $slider->title ?? '') ?: 'Slider başlığı burada görünecek' }}</div>
                    <div class="mt-3 max-w-lg text-sm text-white/80" data-slider-preview-subtitle="true">
                        {{ old('subtitle', $slider->subtitle ?? '') ?: 'Alt başlık ve destek metni burada yer alır.' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
