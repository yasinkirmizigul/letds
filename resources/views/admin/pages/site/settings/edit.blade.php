@extends('admin.layouts.main.app')

@section('content')
    @php
        $extraLanguages = collect($siteLanguages ?? [])->where('code', '!=', $siteDefaultLocale)->values();
        $uiGroups = collect(config('site_ui_labels', []))->groupBy('group');
        $storedTranslations = old('translations');

        if (!is_array($storedTranslations)) {
            $storedTranslations = $settings->translations
                ->mapWithKeys(fn ($translation) => [
                    $translation->locale => [
                        'site_name' => $translation->site_name,
                        'site_tagline' => $translation->site_tagline,
                        'hero_notice' => $translation->hero_notice,
                        'address_line' => $translation->address_line,
                        'map_title' => $translation->map_title,
                        'office_hours' => $translation->office_hours,
                        'footer_note' => $translation->footer_note,
                        'under_construction_title' => $translation->under_construction_title,
                        'under_construction_message' => $translation->under_construction_message,
                        'ui_lines' => is_array($translation->ui_lines) ? $translation->ui_lines : [],
                    ],
                ])
                ->toArray();
        }
    @endphp

    <div class="kt-container-fixed max-w-[96%] grid gap-6" data-page="site.settings.edit">
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <span class="kt-badge kt-badge-sm kt-badge-light-primary w-fit">Site Yönetimi</span>
                <div>
                    <h1 class="text-xl font-semibold">Site Ayarları</h1>
                    <div class="text-sm text-muted-foreground">
                        İletişim, harita, sosyal ağlar, yapım aşaması uyarıları ve arayüz metinlerini merkezi olarak yönet.
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-3xl app-stat-card p-5"><div class="text-sm text-muted-foreground">Sayfa</div><div class="mt-2 text-3xl font-semibold">{{ $stats['pages'] ?? 0 }}</div></div>
            <div class="rounded-3xl app-stat-card p-5"><div class="text-sm text-muted-foreground">Slider</div><div class="mt-2 text-3xl font-semibold text-primary">{{ $stats['sliders'] ?? 0 }}</div></div>
            <div class="rounded-3xl app-stat-card p-5"><div class="text-sm text-muted-foreground">SSS</div><div class="mt-2 text-3xl font-semibold text-success">{{ $stats['faqs'] ?? 0 }}</div></div>
            <div class="rounded-3xl app-stat-card p-5"><div class="text-sm text-muted-foreground">Menü Öğesi</div><div class="mt-2 text-3xl font-semibold text-warning">{{ $stats['navigation'] ?? 0 }}</div></div>
            <div class="rounded-3xl app-stat-card p-5"><div class="text-sm text-muted-foreground">Sayaç</div><div class="mt-2 text-3xl font-semibold text-danger">{{ $stats['counters'] ?? 0 }}</div></div>
        </div>

        <form method="POST" action="{{ route('admin.site.settings.update') }}" class="grid gap-6">
            @csrf
            @method('PUT')

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_420px]">
                <div class="grid gap-6">
                    <div class="kt-card">
                        <div class="kt-card-header py-5">
                            <div>
                                <h3 class="kt-card-title">Varsayılan Dil İçeriği</h3>
                                <div class="text-sm text-muted-foreground">Marka, hero ve footer metinlerinin temel sürümü.</div>
                            </div>
                        </div>

                        <div class="kt-card-content grid gap-4 p-6">
                            <div class="grid gap-2">
                                <label class="kt-form-label">Site Adı</label>
                                <input name="site_name" class="kt-input" value="{{ old('site_name', $settings->site_name) }}">
                            </div>

                            <div class="grid gap-2">
                                <label class="kt-form-label">Kısa Slogan</label>
                                <input name="site_tagline" class="kt-input" value="{{ old('site_tagline', $settings->site_tagline) }}">
                            </div>

                            <div class="grid gap-2">
                                <label class="kt-form-label">Hero Bildirimi</label>
                                <textarea name="hero_notice" rows="3" class="kt-textarea">{{ old('hero_notice', $settings->hero_notice) }}</textarea>
                            </div>

                            <div class="grid gap-2">
                                <label class="kt-form-label">Footer Notu</label>
                                <textarea name="footer_note" rows="3" class="kt-textarea">{{ old('footer_note', $settings->footer_note) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="kt-card">
                        <div class="kt-card-header py-5">
                            <div>
                                <h3 class="kt-card-title">İletişim ve Harita</h3>
                                <div class="text-sm text-muted-foreground">Ziyaretçinin seni bulacağı temel temas noktaları.</div>
                            </div>
                        </div>

                        <div class="kt-card-content grid gap-4 p-6">
                            <div class="grid gap-4 lg:grid-cols-3">
                                <div class="grid gap-2">
                                    <label class="kt-form-label">E-posta</label>
                                    <input name="contact_email" class="kt-input" value="{{ old('contact_email', $settings->contact_email) }}">
                                </div>
                                <div class="grid gap-2">
                                    <label class="kt-form-label">Telefon</label>
                                    <input name="contact_phone" class="kt-input" value="{{ old('contact_phone', $settings->contact_phone) }}">
                                </div>
                                <div class="grid gap-2">
                                    <label class="kt-form-label">WhatsApp</label>
                                    <input name="whatsapp_phone" class="kt-input" value="{{ old('whatsapp_phone', $settings->whatsapp_phone) }}">
                                </div>
                            </div>

                            <div class="grid gap-2">
                                <label class="kt-form-label">Adres</label>
                                <textarea name="address_line" rows="3" class="kt-textarea">{{ old('address_line', $settings->address_line) }}</textarea>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-2">
                                <div class="grid gap-2">
                                    <label class="kt-form-label">Harita Başlığı</label>
                                    <input name="map_title" class="kt-input" value="{{ old('map_title', $settings->map_title) }}">
                                </div>
                                <div class="grid gap-2">
                                    <label class="kt-form-label">Mesai Bilgisi</label>
                                    <textarea name="office_hours" rows="3" class="kt-textarea">{{ old('office_hours', $settings->office_hours) }}</textarea>
                                </div>
                            </div>

                            <div class="grid gap-2">
                                <label class="kt-form-label">Embed / Harita URL</label>
                                <textarea name="map_embed_url" rows="4" class="kt-textarea" placeholder="https://www.google.com/maps/embed?...">{{ old('map_embed_url', $settings->map_embed_url) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="kt-card">
                        <div class="kt-card-header py-5">
                            <div>
                                <h3 class="kt-card-title">Sosyal Ağlar</h3>
                                <div class="text-sm text-muted-foreground">Footer ve iletişim alanında gösterilecek bağlantılar.</div>
                            </div>
                        </div>

                        <div class="kt-card-content grid gap-4 p-6 lg:grid-cols-2">
                            @foreach(['instagram' => 'Instagram', 'facebook' => 'Facebook', 'linkedin' => 'LinkedIn', 'youtube' => 'YouTube', 'x' => 'X / Twitter'] as $key => $label)
                                <div class="grid gap-2">
                                    <label class="kt-form-label">{{ $label }}</label>
                                    <input name="social_links[{{ $key }}]" class="kt-input" value="{{ old('social_links.' . $key, $settings->social($key)) }}" placeholder="https://...">
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="kt-card">
                        <div class="kt-card-header py-5">
                            <div>
                                <h3 class="kt-card-title">Arayüz Metinleri</h3>
                                <div class="text-sm text-muted-foreground">Navbar, ana sayfa ve içerik şablonlarında görünen sabit metinleri buradan çevir.</div>
                            </div>
                        </div>

                        <div class="kt-card-content grid gap-6 p-6">
                            @foreach($uiGroups as $group => $items)
                                <div class="rounded-[28px] app-surface-card p-5">
                                    <div class="mb-4 text-sm font-semibold uppercase tracking-[0.24em] text-muted-foreground">{{ $group }}</div>
                                    <div class="grid gap-4 lg:grid-cols-2">
                                        @foreach($items as $key => $item)
                                            <div class="grid gap-2">
                                                <label class="kt-form-label">{{ $item['label'] }}</label>
                                                <input
                                                    name="ui_lines[{{ $key }}]"
                                                    class="kt-input"
                                                    value="{{ old("ui_lines.$key", $settings->uiLine($key, $siteDefaultLocale)) }}"
                                                    placeholder="{{ $item['placeholder'] ?? '' }}"
                                                >
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="kt-card">
                        <div class="kt-card-header py-5">
                            <div>
                                <h3 class="kt-card-title">Dil Varyantları</h3>
                                <div class="text-sm text-muted-foreground">Her ek dil için marka ve arayüz metinlerini ayrı ayrı tanımla.</div>
                            </div>
                        </div>

                        <div class="kt-card-content p-6">
                            @if($extraLanguages->isEmpty())
                                <div class="rounded-3xl border border-dashed border-border px-6 py-10 text-center text-sm text-muted-foreground">
                                    Yeni bir dil eklediğinde ayar çevirileri burada görünecek.
                                </div>
                            @else
                                <div class="kt-tabs kt-tabs-line mb-6" data-kt-tabs="true">
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($extraLanguages as $language)
                                            <button class="kt-tab-toggle {{ $loop->first ? 'active' : '' }}" data-kt-tab-toggle="#settings_translation_{{ $language->code }}">
                                                {{ $language->native_name }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>

                                @foreach($extraLanguages as $language)
                                    @php
                                        $row = $storedTranslations[$language->code] ?? [];
                                        $rowUiLines = data_get($row, 'ui_lines', []);
                                    @endphp

                                    <div id="settings_translation_{{ $language->code }}" class="{{ $loop->first ? '' : 'hidden' }} grid gap-6">
                                        <div class="rounded-[28px] app-surface-card p-5">
                                            <div class="mb-5 flex items-center justify-between gap-3">
                                                <div>
                                                    <div class="font-semibold text-foreground">{{ $language->native_name }}</div>
                                                    <div class="text-sm text-muted-foreground">{{ $language->code }} diline özel marka ve iletişim metinleri</div>
                                                </div>
                                                <span class="kt-badge kt-badge-sm kt-badge-light">{{ $language->code }}</span>
                                            </div>

                                            <div class="grid gap-4">
                                                <div class="grid gap-4 lg:grid-cols-2">
                                                    <div class="grid gap-2">
                                                        <label class="kt-form-label">Site Adı</label>
                                                        <input name="translations[{{ $language->code }}][site_name]" class="kt-input" value="{{ data_get($row, 'site_name', '') }}">
                                                    </div>
                                                    <div class="grid gap-2">
                                                        <label class="kt-form-label">Kısa Slogan</label>
                                                        <input name="translations[{{ $language->code }}][site_tagline]" class="kt-input" value="{{ data_get($row, 'site_tagline', '') }}">
                                                    </div>
                                                </div>

                                                <div class="grid gap-2">
                                                    <label class="kt-form-label">Hero Bildirimi</label>
                                                    <textarea name="translations[{{ $language->code }}][hero_notice]" rows="3" class="kt-textarea">{{ data_get($row, 'hero_notice', '') }}</textarea>
                                                </div>

                                                <div class="grid gap-2">
                                                    <label class="kt-form-label">Adres</label>
                                                    <textarea name="translations[{{ $language->code }}][address_line]" rows="3" class="kt-textarea">{{ data_get($row, 'address_line', '') }}</textarea>
                                                </div>

                                                <div class="grid gap-4 lg:grid-cols-2">
                                                    <div class="grid gap-2">
                                                        <label class="kt-form-label">Harita Başlığı</label>
                                                        <input name="translations[{{ $language->code }}][map_title]" class="kt-input" value="{{ data_get($row, 'map_title', '') }}">
                                                    </div>
                                                    <div class="grid gap-2">
                                                        <label class="kt-form-label">Mesai Bilgisi</label>
                                                        <textarea name="translations[{{ $language->code }}][office_hours]" rows="3" class="kt-textarea">{{ data_get($row, 'office_hours', '') }}</textarea>
                                                    </div>
                                                </div>

                                                <div class="grid gap-2">
                                                    <label class="kt-form-label">Footer Notu</label>
                                                    <textarea name="translations[{{ $language->code }}][footer_note]" rows="3" class="kt-textarea">{{ data_get($row, 'footer_note', '') }}</textarea>
                                                </div>

                                                <div class="grid gap-4 lg:grid-cols-2">
                                                    <div class="grid gap-2">
                                                        <label class="kt-form-label">Yapım Aşaması Başlığı</label>
                                                        <input name="translations[{{ $language->code }}][under_construction_title]" class="kt-input" value="{{ data_get($row, 'under_construction_title', '') }}">
                                                    </div>
                                                    <div class="grid gap-2">
                                                        <label class="kt-form-label">Yapım Aşaması Mesajı</label>
                                                        <textarea name="translations[{{ $language->code }}][under_construction_message]" rows="3" class="kt-textarea">{{ data_get($row, 'under_construction_message', '') }}</textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        @foreach($uiGroups as $group => $items)
                                            <div class="rounded-[28px] app-surface-card p-5">
                                                <div class="mb-4 text-sm font-semibold uppercase tracking-[0.24em] text-muted-foreground">{{ $group }}</div>
                                                <div class="grid gap-4 lg:grid-cols-2">
                                                    @foreach($items as $key => $item)
                                                        <div class="grid gap-2">
                                                            <label class="kt-form-label">{{ $item['label'] }}</label>
                                                            <input
                                                                name="translations[{{ $language->code }}][ui_lines][{{ $key }}]"
                                                                class="kt-input"
                                                                value="{{ data_get($rowUiLines, $key, '') }}"
                                                                placeholder="{{ $item['placeholder'] ?? '' }}"
                                                            >
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>

                <div class="grid gap-6 self-start xl:sticky xl:top-6">
                    <div class="kt-card">
                        <div class="kt-card-header py-5">
                            <div>
                                <h3 class="kt-card-title">Yapım Aşaması</h3>
                                <div class="text-sm text-muted-foreground">Geçici bakım veya lansman öncesi uyarı alanı.</div>
                            </div>
                        </div>

                        <div class="kt-card-content grid gap-4 p-6">
                            <label class="flex items-start gap-3 rounded-2xl app-surface-card app-surface-card--soft p-4">
                                <input type="hidden" name="under_construction_enabled" value="0">
                                <input type="checkbox" name="under_construction_enabled" value="1" class="kt-checkbox mt-1" @checked(old('under_construction_enabled', $settings->under_construction_enabled))>
                                <span>
                                    <span class="block font-medium text-foreground">Yapım aşaması mesajını göster</span>
                                    <span class="text-sm text-muted-foreground">Açıldığında site üst alanında dikkat kartı olarak görünür.</span>
                                </span>
                            </label>

                            <div class="grid gap-2">
                                <label class="kt-form-label">Başlık</label>
                                <input name="under_construction_title" class="kt-input" value="{{ old('under_construction_title', $settings->under_construction_title) }}">
                            </div>

                            <div class="grid gap-2">
                                <label class="kt-form-label">Mesaj</label>
                                <textarea name="under_construction_message" rows="4" class="kt-textarea">{{ old('under_construction_message', $settings->under_construction_message) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[28px] app-surface-card p-5">
                        <div class="text-xs uppercase tracking-[0.24em] text-muted-foreground">Canlı Önizleme</div>
                        <div class="mt-4 grid gap-3">
                            <div class="text-xl font-semibold text-foreground">{{ old('site_name', $settings->site_name) ?: config('app.name') }}</div>
                            <div class="text-sm text-muted-foreground">{{ old('site_tagline', $settings->site_tagline) ?: 'Marka mesajın burada görünecek.' }}</div>
                            <div class="rounded-2xl app-surface-card app-surface-card--soft p-4 text-sm text-muted-foreground">
                                {{ old('hero_notice', $settings->hero_notice) ?: 'Hero bildirimi veya kampanya mesajı burada görünecek.' }}
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="kt-btn kt-btn-primary">Site Ayarlarını Kaydet</button>
                </div>
            </div>
        </form>
    </div>
@endsection
