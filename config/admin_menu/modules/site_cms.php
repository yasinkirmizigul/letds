<?php

return [
    [
        'type' => 'accordion',
        'title' => 'Site Yapılandırması',
        'icon' => 'ki-filled ki-abstract-26 text-lg',
        'permAny' => [
            'site_languages.view',
            'site_settings.view',
        ],
        'children' => [
            [
                'title' => 'Dil Yönetimi',
                'route' => 'admin.site.languages.index',
                'active' => ['admin.site.languages.*'],
                'perm' => 'site_languages.view',
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
