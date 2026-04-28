<?php

return [
    [
        'type' => 'accordion',
        'title' => 'Site Yönetimi',
        'icon' => 'ki-filled ki-abstract-26 text-lg',
        'permAny' => [
            'site_languages.view',
            'site_pages.view',
            'site_faqs.view',
            'site_counters.view',
            'site_navigation.view',
            'site_settings.view',
            'home_sliders.view',
            'site_payments.view',
        ],
        'children' => [
            [
                'title' => 'Dil Yönetimi',
                'route' => 'admin.site.languages.index',
                'active' => ['admin.site.languages.*'],
                'perm' => 'site_languages.view',
            ],
            [
                'title' => 'İçerik Üretimi',
                'route' => 'admin.site.pages.index',
                'active' => ['admin.site.pages.*'],
                'perm' => 'site_pages.view',
            ],
            [
                'title' => 'Sıkça Sorulan Sorular',
                'route' => 'admin.site.faqs.index',
                'active' => ['admin.site.faqs.*'],
                'perm' => 'site_faqs.view',
            ],
            [
                'title' => 'Sayaçlar',
                'route' => 'admin.site.counters.index',
                'active' => ['admin.site.counters.*'],
                'perm' => 'site_counters.view',
            ],
            [
                'title' => 'Menü Yönetimi',
                'route' => 'admin.site.navigation.index',
                'active' => ['admin.site.navigation.*'],
                'perm' => 'site_navigation.view',
            ],
            [
                'title' => 'Ana Sayfa Slider',
                'route' => 'admin.site.sliders.index',
                'active' => ['admin.site.sliders.*'],
                'perm' => 'home_sliders.view',
            ],
            [
                'title' => 'Ödeme Entegrasyonları',
                'route' => 'admin.site.payments.index',
                'active' => ['admin.site.payments.*'],
                'perm' => 'site_payments.view',
            ],
            [
                'title' => 'Site Ayarları',
                'route' => 'admin.site.settings.edit',
                'active' => ['admin.site.settings.*'],
                'perm' => 'site_settings.view',
            ],
        ],
    ],
];
