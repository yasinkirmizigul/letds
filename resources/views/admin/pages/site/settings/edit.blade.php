@extends('admin.layouts.main.app')

@section('content')
    @php
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
                        'member_terms_title' => $translation->member_terms_title,
                        'member_terms_summary' => $translation->member_terms_summary,
                        'member_terms_content' => $translation->member_terms_content,
                        'under_construction_title' => $translation->under_construction_title,
                        'under_construction_message' => $translation->under_construction_message,
                        'ui_lines' => is_array($translation->ui_lines) ? $translation->ui_lines : [],
                    ],
                ])
                ->toArray();
        }

        $defaultUiLines = old('ui_lines');
        if (!is_array($defaultUiLines)) {
            $defaultUiLines = collect(config('site_ui_labels', []))
                ->mapWithKeys(fn ($item, $key) => [$key => $settings->uiLine($key, $siteDefaultLocale)])
                ->all();
        }

        $mailNotificationsEnabled = old('mail_notifications_enabled', $settings->mail_notifications_enabled);
        $notifyContactMessages = old('notify_contact_messages', $settings->notify_contact_messages ?? true);
        $notifyAppointments = old('notify_appointments', $settings->notify_appointments ?? true);
        $smtpScheme = old('smtp_scheme', $settings->smtp_scheme ?: 'smtp');
        $smtpPasswordIsSet = filled($settings->smtp_password);

        $localizedSettingsDefaultValues = [
            'site_name' => old('site_name', $settings->site_name),
            'site_tagline' => old('site_tagline', $settings->site_tagline),
            'hero_notice' => old('hero_notice', $settings->hero_notice),
            'address_line' => old('address_line', $settings->address_line),
            'map_title' => old('map_title', $settings->map_title),
            'office_hours' => old('office_hours', $settings->office_hours),
            'footer_note' => old('footer_note', $settings->footer_note),
            'member_terms_title' => old('member_terms_title', $settings->member_terms_title ?: config('membership_terms.title')),
            'member_terms_summary' => old('member_terms_summary', $settings->member_terms_summary ?: config('membership_terms.summary')),
            'member_terms_content' => old('member_terms_content', $settings->member_terms_content ?: config('membership_terms.content')),
            'under_construction_title' => old('under_construction_title', $settings->under_construction_title),
            'under_construction_message' => old('under_construction_message', $settings->under_construction_message),
            'ui_lines' => $defaultUiLines,
        ];

        $localizedSettingsFields = [
            [
                'type' => 'section',
                'label' => 'Marka ve görünür metinler',
                'description' => 'Site adı, slogan ve ziyaretçinin ilk gördüğü temel mesaj alanları.',
                'wrapper_class' => 'lg:col-span-2',
            ],
            [
                'name' => 'site_name',
                'label' => 'Site Adı',
            ],
            [
                'name' => 'site_tagline',
                'label' => 'Kısa Slogan',
            ],
            [
                'name' => 'hero_notice',
                'type' => 'textarea',
                'rows' => 3,
                'label' => 'Hero Bildirimi',
                'wrapper_class' => 'grid gap-2 lg:col-span-2',
            ],
            [
                'name' => 'footer_note',
                'type' => 'textarea',
                'rows' => 3,
                'label' => 'Footer Notu',
                'wrapper_class' => 'grid gap-2 lg:col-span-2',
            ],
            [
                'type' => 'section',
                'label' => 'Üyelik bilgilendirmesi',
                'description' => 'Kayıt formunda okunup kabul edilmesi gereken metinleri her dil için ayrı yönetin.',
                'wrapper_class' => 'lg:col-span-2',
            ],
            [
                'name' => 'member_terms_title',
                'label' => 'Bilgilendirme Başlığı',
            ],
            [
                'name' => 'member_terms_summary',
                'type' => 'textarea',
                'rows' => 3,
                'label' => 'Kısa Özet',
                'wrapper_class' => 'grid gap-2 lg:col-span-2',
            ],
            [
                'name' => 'member_terms_content',
                'type' => 'textarea',
                'rows' => 10,
                'label' => 'Bilgilendirme Metni',
                'wrapper_class' => 'grid gap-2 lg:col-span-2',
            ],
            [
                'type' => 'section',
                'label' => 'İletişim ve harita metinleri',
                'description' => 'Adres, harita başlığı ve mesai notu gibi dil bazlı görünen alanlar.',
                'wrapper_class' => 'lg:col-span-2',
            ],
            [
                'name' => 'address_line',
                'type' => 'textarea',
                'rows' => 3,
                'label' => 'Adres',
                'wrapper_class' => 'grid gap-2 lg:col-span-2',
            ],
            [
                'name' => 'map_title',
                'label' => 'Harita Başlığı',
            ],
            [
                'name' => 'office_hours',
                'type' => 'textarea',
                'rows' => 3,
                'label' => 'Mesai Bilgisi',
            ],
            [
                'type' => 'section',
                'label' => 'Yapım aşaması metinleri',
                'description' => 'Bakım veya açılış öncesi ziyaretçiye gösterilen içerikler.',
                'wrapper_class' => 'lg:col-span-2',
            ],
            [
                'name' => 'under_construction_title',
                'label' => 'Yapım Aşaması Başlığı',
            ],
            [
                'name' => 'under_construction_message',
                'type' => 'textarea',
                'rows' => 4,
                'label' => 'Yapım Aşaması Mesajı',
                'wrapper_class' => 'grid gap-2 lg:col-span-2',
            ],
        ];

        foreach ($uiGroups as $group => $items) {
            $localizedSettingsFields[] = [
                'type' => 'section',
                'label' => $group,
                'description' => 'Bu gruptaki sabit arayüz metinleri her dil için ayrı yönetilir.',
                'wrapper_class' => 'lg:col-span-2',
            ];

            foreach ($items as $key => $item) {
                $localizedSettingsFields[] = [
                    'name' => 'ui_lines.' . $key,
                    'binding' => 'ui_lines.' . $key,
                    'segments' => ['ui_lines', $key],
                    'label' => $item['label'],
                    'placeholder' => $item['placeholder'] ?? '',
                ];
            }
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
                        İletişim, harita, sosyal ağlar, yapım aşaması uyarıları, üyelik bilgilendirmesi ve arayüz metinlerini merkezi olarak yönetin.
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
                <div class="grid gap-6" data-site-settings-card-stack="true">
                    @include('admin.components.localized-content-tabs', [
                        'moduleKey' => 'site_settings',
                        'title' => 'Çok Dilli Site İçeriği',
                        'description' => 'Varsayılan dil ve eklediğiniz diğer diller için görünen metinleri aynı yatay sekme düzeninde yönetin.',
                        'defaultValues' => $localizedSettingsDefaultValues,
                        'storedTranslations' => $storedTranslations,
                        'fields' => $localizedSettingsFields,
                        'contentGridClass' => 'grid gap-5 lg:grid-cols-2',
                        'sectionAccordions' => true,
                    ])

                    <div class="kt-card">
                        <div class="kt-card-header py-5">
                            <div>
                                <h3 class="kt-card-title">İletişim Kanalları ve Harita Entegrasyonu</h3>
                                <div class="text-sm text-muted-foreground">Dil fark etmeksizin sabit kalan sistem alanları burada yönetilir.</div>
                            </div>
                        </div>

                        <div class="kt-card-content grid gap-4 p-6">
                            <div class="grid gap-4 lg:grid-cols-3">
                                <div class="grid gap-2">
                                    <label class="kt-form-label">E-posta</label>
                                    <input name="contact_email" type="email" class="kt-input" value="{{ old('contact_email', $settings->contact_email) }}">
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
                                <label class="kt-form-label">Embed / Harita URL</label>
                                <textarea name="map_embed_url" rows="4" class="kt-textarea" placeholder="https://www.google.com/maps/embed?...">{{ old('map_embed_url', $settings->map_embed_url) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="kt-card">
                        <div class="kt-card-header py-5">
                            <div>
                                <h3 class="kt-card-title">E-posta Bildirimleri ve SMTP</h3>
                                <div class="text-sm text-muted-foreground">Panel kullanıcılarına giden mesaj ve randevu e-postaları için gönderim altyapısı.</div>
                            </div>
                        </div>

                        <div class="kt-card-content grid gap-5 p-6">
                            <label class="flex items-start gap-3 rounded-2xl app-surface-card app-surface-card--soft p-4">
                                <input type="hidden" name="mail_notifications_enabled" value="0">
                                <input type="checkbox" name="mail_notifications_enabled" value="1" class="kt-checkbox mt-1" @checked($mailNotificationsEnabled)>
                                <span>
                                    <span class="block font-medium text-foreground">E-posta bildirimlerini aktif et</span>
                                    <span class="text-sm text-muted-foreground">Kapalıyken mesaj ve randevu bildirimleri kuyruğa alınmaz.</span>
                                </span>
                            </label>

                            <div class="grid gap-4 lg:grid-cols-2">
                                <label class="flex items-start gap-3 rounded-2xl border border-border bg-background/70 p-4">
                                    <input type="hidden" name="notify_contact_messages" value="0">
                                    <input type="checkbox" name="notify_contact_messages" value="1" class="kt-checkbox mt-1" @checked($notifyContactMessages)>
                                    <span>
                                        <span class="block font-medium text-foreground">Mesaj bildirimi</span>
                                        <span class="text-sm text-muted-foreground">İletişim formu mesajları seçilen panel kullanıcısına gider.</span>
                                    </span>
                                </label>

                                <label class="flex items-start gap-3 rounded-2xl border border-border bg-background/70 p-4">
                                    <input type="hidden" name="notify_appointments" value="0">
                                    <input type="checkbox" name="notify_appointments" value="1" class="kt-checkbox mt-1" @checked($notifyAppointments)>
                                    <span>
                                        <span class="block font-medium text-foreground">Randevu bildirimi</span>
                                        <span class="text-sm text-muted-foreground">Yeni, taşınan veya iptal edilen randevular ilgili panel kullanıcısına gider.</span>
                                    </span>
                                </label>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-2">
                                <div class="grid gap-2">
                                    <label class="kt-form-label">Gönderen E-posta</label>
                                    <input name="mail_from_address" type="email" class="kt-input" value="{{ old('mail_from_address', $settings->mail_from_address ?: $settings->contact_email) }}" placeholder="noreply@site.com">
                                </div>
                                <div class="grid gap-2">
                                    <label class="kt-form-label">Gönderen Adı</label>
                                    <input name="mail_from_name" class="kt-input" value="{{ old('mail_from_name', $settings->mail_from_name ?: $settings->site_name) }}" placeholder="{{ config('app.name') }}">
                                </div>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-4">
                                <div class="grid gap-2 lg:col-span-2">
                                    <label class="kt-form-label">SMTP Host</label>
                                    <input name="smtp_host" class="kt-input" value="{{ old('smtp_host', $settings->smtp_host) }}" placeholder="smtp.domain.com">
                                </div>
                                <div class="grid gap-2">
                                    <label class="kt-form-label">Port</label>
                                    <input name="smtp_port" type="number" min="1" max="65535" class="kt-input" value="{{ old('smtp_port', $settings->smtp_port ?: 587) }}">
                                </div>
                                <div class="grid gap-2">
                                    <label class="kt-form-label">Güvenlik</label>
                                    <select name="smtp_scheme" class="kt-select">
                                        <option value="smtp" @selected($smtpScheme === 'smtp')>TLS / STARTTLS</option>
                                        <option value="smtps" @selected($smtpScheme === 'smtps')>SSL</option>
                                        <option value="smtp_plain" @selected($smtpScheme === 'smtp_plain')>Yok</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-3">
                                <div class="grid gap-2">
                                    <label class="kt-form-label">Kullanıcı Adı</label>
                                    <input name="smtp_username" class="kt-input" value="{{ old('smtp_username', $settings->smtp_username) }}" autocomplete="off">
                                </div>
                                <div class="grid gap-2">
                                    <label class="kt-form-label">Parola</label>
                                    <input name="smtp_password" type="password" class="kt-input" value="" autocomplete="new-password" placeholder="{{ $smtpPasswordIsSet ? 'Kayıtlı parola korunur' : '' }}">
                                </div>
                                <div class="grid gap-2">
                                    <label class="kt-form-label">Timeout (sn)</label>
                                    <input name="smtp_timeout" type="number" min="1" max="120" class="kt-input" value="{{ old('smtp_timeout', $settings->smtp_timeout ?: 10) }}">
                                </div>
                            </div>

                            @if($smtpPasswordIsSet)
                                <label class="flex items-center gap-3 text-sm text-muted-foreground">
                                    <input type="hidden" name="smtp_password_clear" value="0">
                                    <input type="checkbox" name="smtp_password_clear" value="1" class="kt-checkbox">
                                    Kayıtlı SMTP parolasını temizle
                                </label>
                            @endif

                            <div class="grid gap-3 rounded-2xl border border-dashed border-border bg-background/70 p-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                                <div class="grid gap-2">
                                    <label class="kt-form-label">Test E-postası</label>
                                    <input form="site-settings-test-mail" name="test_email" type="email" class="kt-input" value="{{ old('test_email', auth()->user()?->email) }}" placeholder="mail@domain.com">
                                </div>
                                <button type="submit" form="site-settings-test-mail" class="kt-btn kt-btn-light-primary">Test Gönder</button>
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
                </div>

                <div class="grid gap-6 self-start xl:sticky xl:top-6" data-site-settings-card-stack="true">
                    <div class="kt-card">
                        <div class="kt-card-header py-5">
                            <div>
                                <h3 class="kt-card-title">Üyelik Metni Versiyonu</h3>
                                <div class="text-sm text-muted-foreground">Kayıt sırasında hangi metin sürümünün kabul edildiğini takip etmek için kullanılır.</div>
                            </div>
                        </div>

                        <div class="kt-card-content grid gap-4 p-6">
                            <div class="grid gap-2">
                                <label class="kt-form-label">Versiyon</label>
                                <input
                                    name="member_terms_version"
                                    class="kt-input"
                                    value="{{ old('member_terms_version', $settings->member_terms_version ?: config('membership_terms.version')) }}"
                                    placeholder="1.0"
                                >
                            </div>
                        </div>
                    </div>

                    <div class="kt-card">
                        <div class="kt-card-header py-5">
                            <div>
                                <h3 class="kt-card-title">Yapım Aşaması Durumu</h3>
                                <div class="text-sm text-muted-foreground">Aktivasyon burada, metin içerikleri ise dil sekmelerinde yönetilir.</div>
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

                            <div class="rounded-2xl border border-dashed border-border bg-background/70 px-4 py-4 text-sm leading-6 text-muted-foreground">
                                Başlık ve açıklama metinleri yukarıdaki çok dilli sekmelerden yönetilir. Böylece her dil için farklı yapım aşaması mesajı gösterebilirsiniz.
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

        <form id="site-settings-test-mail" method="POST" action="{{ route('admin.site.settings.test-mail') }}" class="hidden">
            @csrf
        </form>
    </div>
@endsection
