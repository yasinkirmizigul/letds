<?php

$surfacePatterns = [
    'none' => ['label' => 'Desensiz', 'description' => 'Sadece zemin rengi veya fotoğraf görünür.'],
    'carbon' => ['label' => 'Carbon Fiber', 'description' => 'Katmanlı ve teknik örgü dokusu.'],
    'micro-grid' => ['label' => 'Mikro Grid', 'description' => 'İnce, modern kare çizgiler.'],
    'pixel-grid' => ['label' => 'Piksel Kareler', 'description' => 'Küçük karelerden oluşan yumuşak mozaik.'],
    'dots' => ['label' => 'Nokta Matrisi', 'description' => 'Minimal ve dengeli nokta dokusu.'],
    'diagonal' => ['label' => 'Diyagonal', 'description' => 'İnce çapraz çizgiler.'],
    'blueprint' => ['label' => 'Blueprint', 'description' => 'Teknik çizim hissi veren çift grid.'],
    'rings' => ['label' => 'Halkalar', 'description' => 'Yumuşak topografik halka ritmi.'],
    'grain' => ['label' => 'Film Greni', 'description' => 'Fotoğraflara doğal yüzey hissi katar.'],
];

$surfaceBlendModes = [
    'soft-light' => 'Yumuşak ışık (Önerilen)',
    'overlay' => 'Overlay / Kontrast',
    'normal' => 'Normal',
    'multiply' => 'Çarpma / Koyulaştır',
    'screen' => 'Ekran / Aydınlat',
];

$surfaceColorEffects = [
    'solid' => 'Düz renk',
    'gradient' => 'Gradyan',
];

$surfaceField = static function (
    string $prefix,
    string $side,
    string $label,
    string $defaultBackground,
    string $defaultPatternColor,
) use ($surfacePatterns, $surfaceBlendModes, $surfaceColorEffects): array {
    $keyPrefix = $prefix.$side;

    return [
        'key' => $keyPrefix.'_surface',
        'label' => $label,
        'description' => 'Renk, desen ve fotoğraf üstü doku davranışını birlikte yönetin.',
        'type' => 'surface',
        'side' => $side,
        'fields' => [
            ['key' => $keyPrefix.'_background_color', 'label' => 'Zemin Rengi', 'type' => 'color', 'role' => 'background', 'default' => $defaultBackground],
            ['key' => $keyPrefix.'_color_effect', 'label' => 'Renk Uygulaması', 'type' => 'select', 'role' => 'effect', 'default' => $side === 'after' ? 'gradient' : 'solid', 'options' => $surfaceColorEffects],
            ['key' => $keyPrefix.'_pattern_color', 'label' => 'Desen Rengi', 'type' => 'color', 'role' => 'pattern-color', 'default' => $defaultPatternColor],
            ['key' => $keyPrefix.'_pattern', 'label' => 'Desen', 'type' => 'select', 'role' => 'pattern', 'default' => 'none', 'options' => $surfacePatterns],
            ['key' => $keyPrefix.'_pattern_opacity', 'label' => 'Desen Yoğunluğu', 'type' => 'range', 'role' => 'opacity', 'default' => 18, 'min' => 0, 'max' => 70, 'step' => 1, 'unit' => '%'],
            ['key' => $keyPrefix.'_pattern_scale', 'label' => 'Desen Ölçeği', 'type' => 'range', 'role' => 'scale', 'default' => 28, 'min' => 8, 'max' => 96, 'step' => 2, 'unit' => 'px'],
            ['key' => $keyPrefix.'_pattern_blur', 'label' => 'Yumuşaklık', 'type' => 'range', 'role' => 'blur', 'default' => 0, 'min' => 0, 'max' => 4, 'step' => 0.25, 'unit' => 'px'],
            ['key' => $keyPrefix.'_pattern_blend', 'label' => 'Fotoğraf Karışımı', 'type' => 'select', 'role' => 'blend', 'default' => 'soft-light', 'options' => $surfaceBlendModes],
        ],
    ];
};

$computerPaletteFields = static function (string $prefix = ''): array {
    return [
        [
            'key' => $prefix.'computer_pv_fill_mode',
            'label' => 'Probablue Logo Gövdesi',
            'type' => 'select',
            'default' => 'gradient',
            'options' => [
                'gradient' => 'Gradyan',
                'solid' => 'Düz renk',
            ],
            'wrapper_class' => 'sm:col-span-2 xl:col-span-3',
        ],
        ['key' => $prefix.'computer_pv_body_start_color', 'label' => 'Logo Gövde Başlangıcı', 'type' => 'color', 'default' => '#072247'],
        ['key' => $prefix.'computer_pv_body_end_color', 'label' => 'Logo Gövde Bitişi', 'type' => 'color', 'default' => '#0060ea'],
        ['key' => $prefix.'computer_pv_bar_light_color', 'label' => 'Açık Veri Çubukları', 'type' => 'color', 'default' => '#a0c7fc'],
        ['key' => $prefix.'computer_pv_bar_mid_color', 'label' => 'Orta Veri Çubukları', 'type' => 'color', 'default' => '#7eaff8'],
        ['key' => $prefix.'computer_pv_bar_vivid_color', 'label' => 'Canlı Veri ve Akış', 'type' => 'color', 'default' => '#016af6'],
        ['key' => $prefix.'computer_pv_bar_dark_color', 'label' => 'Koyu Veri ve Akış', 'type' => 'color', 'default' => '#0046d6'],
    ];
};

return [
    'key' => 'concept-home',
    'title' => 'Ana Sayfa Yönetimi',
    'description' => 'Bu projeye özel ana sayfanın metin, bağlantı ve renk ayarları.',
    'default_backgrounds' => [
        'light' => 'assets/site/home/images/home-background-light.svg',
        'dark' => 'assets/site/home/images/home-background-dark.svg',
    ],

    'modes' => [
        'analysis' => [
            'label' => 'İstatistiksel Analiz',
            'icon' => 'chart',
            'label_key' => 'analysis_tab_label',
            'hero_title_key' => 'hero_title',
            'cta_label_key' => 'cta_label',
            'cta_url_key' => 'cta_url',
            'settings_prefix' => '',
        ],
        'consultation' => [
            'label' => 'İstatistiksel Danışma',
            'icon' => 'message',
            'label_key' => 'consultation_tab_label',
            'hero_title_key' => 'consultation_hero_title',
            'cta_label_key' => 'consultation_cta_label',
            'cta_url_key' => 'consultation_cta_url',
            'settings_prefix' => 'consultation_',
        ],
    ],

    'content_fields' => [
        [
            'type' => 'section',
            'label' => 'Genel Sayfa Bilgileri',
            'description' => 'Her iki sekmede ortak kullanılan tarayıcı bilgileri.',
        ],
        [
            'key' => 'browser_title',
            'label' => 'Tarayıcı Başlığı',
            'type' => 'text',
            'default' => '',
            'rules' => ['nullable', 'string', 'max:255'],
        ],
        [
            'type' => 'section',
            'label' => 'İstatistiksel Analiz',
            'description' => 'Analiz sekmesinin üst menü, ana mesaj ve buton içeriği.',
            'mode' => 'analysis',
        ],
        [
            'key' => 'analysis_tab_label',
            'label' => 'Sekme Metni',
            'type' => 'text',
            'default' => 'İstatistiksel Analiz',
            'rules' => ['required', 'string', 'max:80'],
            'mode' => 'analysis',
            'colors' => [
                ['key' => 'analysis_tab_after_text_color', 'label' => 'Sol panel', 'default' => '#ffffff'],
                ['key' => 'analysis_tab_before_text_color', 'label' => 'Sağ panel', 'default' => '#445963'],
            ],
        ],
        [
            'key' => 'hero_title',
            'label' => 'Ana Başlık',
            'type' => 'textarea',
            'rows' => 3,
            'default' => 'The combination of great design and diligent app development.',
            'rules' => ['required', 'string', 'max:500'],
            'wrapper_class' => 'grid gap-2 lg:col-span-2',
            'mode' => 'analysis',
            'colors' => [
                ['key' => 'hero_after_text_color', 'label' => 'Sol panel', 'default' => '#ffffff'],
                ['key' => 'hero_before_text_color', 'label' => 'Sağ panel', 'default' => '#445963'],
            ],
        ],
        [
            'key' => 'cta_label',
            'label' => 'Buton Metni',
            'type' => 'text',
            'default' => 'VIEW THEMES',
            'rules' => ['required', 'string', 'max:120'],
            'mode' => 'analysis',
            'colors' => [
                ['key' => 'cta_after_text_color', 'label' => 'Sol panel', 'default' => '#ffffff'],
                ['key' => 'cta_before_text_color', 'label' => 'Sağ panel', 'default' => '#eb5155'],
            ],
        ],
        [
            'key' => 'cta_url',
            'label' => 'Buton Bağlantısı',
            'type' => 'url',
            'placeholder' => '/iletisim veya https://...',
            'default' => '',
            'rules' => ['nullable', 'string', 'max:500'],
            'mode' => 'analysis',
        ],

        [
            'type' => 'section',
            'label' => 'İstatistiksel Danışma',
            'description' => 'Danışma sekmesinin üst menü, ana mesaj ve buton içeriği.',
            'mode' => 'consultation',
        ],
        [
            'key' => 'consultation_tab_label',
            'label' => 'Sekme Metni',
            'type' => 'text',
            'default' => 'İstatistiksel Danışma',
            'rules' => ['required', 'string', 'max:80'],
            'mode' => 'consultation',
            'colors' => [
                ['key' => 'consultation_tab_after_text_color', 'label' => 'Sol panel', 'default' => '#ffffff'],
                ['key' => 'consultation_tab_before_text_color', 'label' => 'Sağ panel', 'default' => '#293f4b'],
            ],
        ],
        [
            'key' => 'consultation_hero_title',
            'label' => 'Ana Başlık',
            'type' => 'textarea',
            'rows' => 3,
            'default' => 'Verilerinizi doğru kararlarla buluşturan istatistiksel danışmanlık.',
            'rules' => ['required', 'string', 'max:500'],
            'wrapper_class' => 'grid gap-2 lg:col-span-2',
            'mode' => 'consultation',
            'colors' => [
                ['key' => 'consultation_hero_after_text_color', 'label' => 'Sol panel', 'default' => '#ffffff'],
                ['key' => 'consultation_hero_before_text_color', 'label' => 'Sağ panel', 'default' => '#293f4b'],
            ],
        ],
        [
            'key' => 'consultation_cta_label',
            'label' => 'Buton Metni',
            'type' => 'text',
            'default' => 'DANIŞMANLIK ALIN',
            'rules' => ['required', 'string', 'max:120'],
            'mode' => 'consultation',
            'colors' => [
                ['key' => 'consultation_cta_after_text_color', 'label' => 'Sol panel', 'default' => '#ffffff'],
                ['key' => 'consultation_cta_before_text_color', 'label' => 'Sağ panel', 'default' => '#006ae6'],
            ],
        ],
        [
            'key' => 'consultation_cta_url',
            'label' => 'Buton Bağlantısı',
            'type' => 'url',
            'placeholder' => '/iletisim veya https://...',
            'default' => '',
            'rules' => ['nullable', 'string', 'max:500'],
            'mode' => 'consultation',
        ],

        [
            'type' => 'section',
            'label' => 'Bilgi noktası 1',
            'description' => 'Görsel üzerindeki birinci bilgi noktasının iki paneldeki metni.',
        ],
        [
            'key' => 'tooltip_1_title',
            'label' => 'Sağ Panel Metni',
            'type' => 'textarea',
            'rows' => 3,
            'default' => 'We make sleek and modern designs for your business.',
            'rules' => ['required', 'string', 'max:700'],
            'sanitize' => 'html',
            'colors' => [
                ['key' => 'tooltip_1_title_color', 'label' => 'Metin rengi', 'default' => '#ffffff'],
            ],
        ],
        [
            'key' => 'tooltip_1_highlighted_title',
            'label' => 'Sol Panel Metni',
            'type' => 'textarea',
            'rows' => 3,
            'default' => 'We make <span class="color-main">sleek and modern</span> designs for your business.',
            'rules' => ['required', 'string', 'max:700'],
            'sanitize' => 'html',
            'colors' => [
                ['key' => 'tooltip_1_highlighted_title_color', 'label' => 'Metin rengi', 'default' => '#445963'],
            ],
        ],

        [
            'type' => 'section',
            'label' => 'Bilgi noktası 2',
            'description' => 'Görsel üzerindeki ikinci bilgi noktasının iki paneldeki metni.',
        ],
        [
            'key' => 'tooltip_2_title',
            'label' => 'Sağ Panel Metni',
            'type' => 'textarea',
            'rows' => 3,
            'default' => 'Our creations embrace <br>the simplicity to look extraordinary.',
            'rules' => ['required', 'string', 'max:700'],
            'sanitize' => 'html',
            'colors' => [
                ['key' => 'tooltip_2_title_color', 'label' => 'Metin rengi', 'default' => '#ffffff'],
            ],
        ],
        [
            'key' => 'tooltip_2_highlighted_title',
            'label' => 'Sol Panel Metni',
            'type' => 'textarea',
            'rows' => 3,
            'default' => 'Our creations embrace <br>the <span class="color-main">simplicity</span> to look extraordinary.',
            'rules' => ['required', 'string', 'max:700'],
            'sanitize' => 'html',
            'colors' => [
                ['key' => 'tooltip_2_highlighted_title_color', 'label' => 'Metin rengi', 'default' => '#445963'],
            ],
        ],

        [
            'type' => 'section',
            'label' => 'Bilgi noktası 3',
            'description' => 'Görsel üzerindeki üçüncü bilgi noktasının iki paneldeki metni.',
        ],
        [
            'key' => 'tooltip_3_title',
            'label' => 'Sağ Panel Metni',
            'type' => 'textarea',
            'rows' => 3,
            'default' => 'Platforms are built from a solid engine & best experiments.',
            'rules' => ['required', 'string', 'max:700'],
            'sanitize' => 'html',
            'colors' => [
                ['key' => 'tooltip_3_title_color', 'label' => 'Metin rengi', 'default' => '#ffffff'],
            ],
        ],
        [
            'key' => 'tooltip_3_highlighted_title',
            'label' => 'Sol Panel Metni',
            'type' => 'textarea',
            'rows' => 3,
            'default' => 'Platforms are built from a <span class="color-main">solid engine</span> & best experiments.',
            'rules' => ['required', 'string', 'max:700'],
            'sanitize' => 'html',
            'colors' => [
                ['key' => 'tooltip_3_highlighted_title_color', 'label' => 'Metin rengi', 'default' => '#445963'],
            ],
        ],

        [
            'type' => 'section',
            'label' => 'Bilgi noktası 4',
            'description' => 'Görsel üzerindeki dördüncü bilgi noktasının iki paneldeki metni.',
        ],
        [
            'key' => 'tooltip_4_title',
            'label' => 'Sağ Panel Metni',
            'type' => 'textarea',
            'rows' => 3,
            'default' => 'EngineThemes are easy to setup and customize to match your needs.',
            'rules' => ['required', 'string', 'max:700'],
            'sanitize' => 'html',
            'colors' => [
                ['key' => 'tooltip_4_title_color', 'label' => 'Metin rengi', 'default' => '#ffffff'],
            ],
        ],
        [
            'key' => 'tooltip_4_highlighted_title',
            'label' => 'Sol Panel Metni',
            'type' => 'textarea',
            'rows' => 3,
            'default' => 'EngineThemes are <span class="color-main">easy to setup</span> and customize to match your needs.',
            'rules' => ['required', 'string', 'max:700'],
            'sanitize' => 'html',
            'colors' => [
                ['key' => 'tooltip_4_highlighted_title_color', 'label' => 'Metin rengi', 'default' => '#445963'],
            ],
        ],
    ],

    'setting_groups' => [
        [
            'key' => 'behavior',
            'title' => 'Davranış',
            'description' => 'Buton ve bilgi noktalarının görünürlük ayarları.',
            'fields' => [
                [
                    'key' => 'hero_layout',
                    'label' => 'Giriş tasarımı',
                    'type' => 'select',
                    'default' => 'interactive',
                    'options' => [
                        'interactive' => 'Etkileşimli karşılaştırma',
                        'probablue' => 'Probablue bölünmüş görünüm',
                    ],
                ],
                ['key' => 'cta_new_tab', 'label' => 'Butonu yeni sekmede aç', 'type' => 'boolean', 'default' => false],
                ['key' => 'cursor_symbols_enabled', 'label' => 'Fare sembol efektini göster', 'type' => 'boolean', 'default' => true],
                [
                    'key' => 'cursor_symbol_mode',
                    'label' => 'Sembol çalışma biçimi',
                    'type' => 'select',
                    'default' => 'idle',
                    'options' => [
                        'idle' => 'İmleç durduğunda',
                        'moving' => 'İmleç hareket ederken',
                        'both' => 'İkisi birlikte',
                    ],
                ],
                ['key' => 'tooltip_1_enabled', 'label' => 'Bilgi noktası 1', 'type' => 'boolean', 'default' => true],
                ['key' => 'tooltip_2_enabled', 'label' => 'Bilgi noktası 2', 'type' => 'boolean', 'default' => true],
                ['key' => 'tooltip_3_enabled', 'label' => 'Bilgi noktası 3', 'type' => 'boolean', 'default' => true],
                ['key' => 'tooltip_4_enabled', 'label' => 'Bilgi noktası 4', 'type' => 'boolean', 'default' => true],
            ],
        ],
        [
            'key' => 'panels',
            'title' => 'Panel Renkleri',
            'description' => 'İki ana panelin renk, desen, doku ve fotoğraf karışımı ayarları.',
            'mode' => 'analysis',
            'wrapper_class' => 'xl:col-span-2',
            'content_class' => 'lg:grid-cols-2',
            'fields' => [
                $surfaceField('', 'after', 'Sol Panel Yüzeyi', '#ec6367', '#ffffff'),
                $surfaceField('', 'before', 'Sağ Panel Yüzeyi', '#ffffff', '#445963'),
                ['key' => 'after_text_color', 'label' => 'Sol Panel Metni', 'type' => 'color', 'default' => '#ffffff'],
                ['key' => 'before_text_color', 'label' => 'Sağ Panel Metni', 'type' => 'color', 'default' => '#445963'],
                ['key' => 'after_highlight_color', 'label' => 'Sol Panel Vurgusu', 'type' => 'color', 'default' => '#445963'],
                ['key' => 'before_highlight_color', 'label' => 'Sağ Panel Vurgusu', 'type' => 'color', 'default' => '#ec6367'],
            ],
        ],
        [
            'key' => 'controls',
            'title' => 'Noktalar ve Ayırıcı',
            'description' => 'Analiz görünümündeki bilgi noktaları, ayırıcı ve sembol renkleri.',
            'mode' => 'analysis',
            'fields' => [
                ['key' => 'after_hotspot_color', 'label' => 'Sol Panel Noktaları', 'type' => 'color', 'default' => '#ffffff'],
                ['key' => 'before_hotspot_color', 'label' => 'Sağ Panel Noktaları', 'type' => 'color', 'default' => '#ec6367'],
                ['key' => 'drag_handle_color', 'label' => 'Karşılaştırma Tutamacı', 'type' => 'color', 'default' => '#ec6367'],
                ['key' => 'cursor_symbol_after_color', 'label' => 'Sol Panel Uçuşan Semboller', 'type' => 'color', 'default' => '#ffffff'],
                ['key' => 'cursor_symbol_before_color', 'label' => 'Sağ Panel Uçuşan Semboller', 'type' => 'color', 'default' => '#ec6367'],
            ],
        ],
        [
            'key' => 'computer_palette',
            'title' => 'Probablue Logo Paleti',
            'description' => 'Kod dokulu logo sabit kalır; canlı Probablue logosunun gövde, veri ve gradyan renklerini yönetin.',
            'mode' => 'analysis',
            'preview' => 'computer',
            'preview_prefix' => '',
            'wrapper_class' => 'xl:col-span-2',
            'content_class' => 'sm:grid-cols-2 xl:grid-cols-3',
            'fields' => $computerPaletteFields(),
        ],
        [
            'key' => 'buttons',
            'title' => 'Buton Renkleri',
            'description' => 'Butonun iki paneldeki normal ve üzerine gelme renkleri.',
            'mode' => 'analysis',
            'fields' => [
                ['key' => 'cta_after_hover_background', 'label' => 'Sol Panel Hover Zemini', 'type' => 'color', 'default' => '#ffffff'],
                ['key' => 'cta_after_hover_text', 'label' => 'Sol Panel Hover Metni', 'type' => 'color', 'default' => '#eb5155'],
                ['key' => 'cta_before_hover_background', 'label' => 'Sağ Panel Hover Zemini', 'type' => 'color', 'default' => '#eb5155'],
                ['key' => 'cta_before_hover_text', 'label' => 'Sağ Panel Hover Metni', 'type' => 'color', 'default' => '#ffffff'],
            ],
        ],
        [
            'key' => 'consultation_panels',
            'title' => 'Panel Renkleri',
            'description' => 'Danışma görünümünün renk, desen, doku ve fotoğraf karışımı ayarları.',
            'mode' => 'consultation',
            'wrapper_class' => 'xl:col-span-2',
            'content_class' => 'lg:grid-cols-2',
            'fields' => [
                $surfaceField('consultation_', 'after', 'Sol Panel Yüzeyi', '#176b87', '#ffffff'),
                $surfaceField('consultation_', 'before', 'Sağ Panel Yüzeyi', '#f7fafc', '#293f4b'),
                ['key' => 'consultation_after_text_color', 'label' => 'Sol Panel Metni', 'type' => 'color', 'default' => '#ffffff'],
                ['key' => 'consultation_before_text_color', 'label' => 'Sağ Panel Metni', 'type' => 'color', 'default' => '#293f4b'],
                ['key' => 'consultation_after_highlight_color', 'label' => 'Sol Panel Vurgusu', 'type' => 'color', 'default' => '#a7f3d0'],
                ['key' => 'consultation_before_highlight_color', 'label' => 'Sağ Panel Vurgusu', 'type' => 'color', 'default' => '#006ae6'],
            ],
        ],
        [
            'key' => 'consultation_controls',
            'title' => 'Noktalar ve Ayırıcı',
            'description' => 'Danışma görünümündeki bilgi noktaları, ayırıcı ve sembol renkleri.',
            'mode' => 'consultation',
            'fields' => [
                ['key' => 'consultation_after_hotspot_color', 'label' => 'Sol Panel Noktaları', 'type' => 'color', 'default' => '#ffffff'],
                ['key' => 'consultation_before_hotspot_color', 'label' => 'Sağ Panel Noktaları', 'type' => 'color', 'default' => '#006ae6'],
                ['key' => 'consultation_drag_handle_color', 'label' => 'Karşılaştırma Tutamacı', 'type' => 'color', 'default' => '#006ae6'],
                ['key' => 'consultation_cursor_symbol_after_color', 'label' => 'Sol Panel Uçuşan Semboller', 'type' => 'color', 'default' => '#ffffff'],
                ['key' => 'consultation_cursor_symbol_before_color', 'label' => 'Sağ Panel Uçuşan Semboller', 'type' => 'color', 'default' => '#006ae6'],
            ],
        ],
        [
            'key' => 'consultation_computer_palette',
            'title' => 'Probablue Logo Paleti',
            'description' => 'Danışma görünümündeki canlı Probablue logosunun gövde, veri ve gradyan renklerini yönetin.',
            'mode' => 'consultation',
            'preview' => 'computer',
            'preview_prefix' => 'consultation_',
            'wrapper_class' => 'xl:col-span-2',
            'content_class' => 'sm:grid-cols-2 xl:grid-cols-3',
            'fields' => $computerPaletteFields('consultation_'),
        ],
        [
            'key' => 'consultation_buttons',
            'title' => 'Buton Renkleri',
            'description' => 'Danışma butonunun iki paneldeki normal ve hover renkleri.',
            'mode' => 'consultation',
            'fields' => [
                ['key' => 'consultation_cta_after_hover_background', 'label' => 'Sol Panel Hover Zemini', 'type' => 'color', 'default' => '#ffffff'],
                ['key' => 'consultation_cta_after_hover_text', 'label' => 'Sol Panel Hover Metni', 'type' => 'color', 'default' => '#176b87'],
                ['key' => 'consultation_cta_before_hover_background', 'label' => 'Sağ Panel Hover Zemini', 'type' => 'color', 'default' => '#006ae6'],
                ['key' => 'consultation_cta_before_hover_text', 'label' => 'Sağ Panel Hover Metni', 'type' => 'color', 'default' => '#ffffff'],
            ],
        ],
        [
            'key' => 'background',
            'title' => 'Arka Plan Görseli',
            'description' => 'Tema duyarlı varsayılan SVG veya özel fotoğraf, parlaklık ve panel overlay ayarları.',
            'fields' => [
                [
                    'key' => 'background_media_id',
                    'label' => 'Arka Plan Görseli',
                    'type' => 'media',
                    'default' => null,
                    'wrapper_class' => 'sm:col-span-2',
                    'preview' => 'background',
                    'allow_upload' => true,
                    'upload_name' => 'background_image',
                    'clear_flag_name' => 'clear_background_image',
                ],
                ['key' => 'background_brightness', 'label' => 'Fotoğraf Parlaklığı', 'type' => 'range', 'default' => 100, 'min' => 20, 'max' => 120, 'step' => 5, 'unit' => '%'],
                ['key' => 'background_overlay_enabled', 'label' => 'Panel renklerini overlay olarak uygula', 'type' => 'boolean', 'default' => true],
                ['key' => 'background_overlay_opacity', 'label' => 'Overlay Yoğunluğu', 'type' => 'range', 'default' => 65, 'min' => 0, 'max' => 100, 'step' => 5, 'unit' => '%'],
                [
                    'key' => 'background_position',
                    'label' => 'Fotoğraf Konumu',
                    'type' => 'select',
                    'default' => 'center',
                    'options' => [
                        'center' => 'Orta',
                        'top' => 'Üst',
                        'bottom' => 'Alt',
                    ],
                ],
            ],
        ],
        [
            'key' => 'header',
            'title' => 'Üst Menü ve Logo',
            'description' => 'İki sekmenin ortasında kullanılacak logo ve ortak üst menü renkleri.',
            'fields' => [
                ['key' => 'header_logo_media_id', 'label' => 'Orta Logo', 'type' => 'media', 'default' => null, 'wrapper_class' => 'sm:col-span-2'],
                ['key' => 'logo_color', 'label' => 'Varsayılan Logo Rengi', 'type' => 'color', 'default' => '#ffffff'],
                ['key' => 'sticky_header_background', 'label' => 'Sabit Başlık Zemini', 'type' => 'color', 'default' => '#ebeef0'],
                ['key' => 'sticky_logo_color', 'label' => 'Sabit Logo Rengi', 'type' => 'color', 'default' => '#ec6367'],
            ],
        ],
    ],
];
