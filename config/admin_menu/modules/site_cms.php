<?php

return [
    [
        'key' => 'site_configuration',
        'type' => 'accordion',
        'title' => 'Site Yapılandırması',
        'icon' => 'ki-filled ki-abstract-26 text-lg',
        'permAny' => [
            'site_languages.view',
            'site_settings.view',
        ],
        'children' => [
            [
                'key' => 'site_configuration.languages',
                'title' => 'Dil Yönetimi',
                'route' => 'admin.site.languages.index',
                'active' => ['admin.site.languages.*'],
                'perm' => 'site_languages.view',
            ],
            [
                'key' => 'site_configuration.settings',
                'title' => 'Site Ayarları',
                'route' => 'admin.site.settings.edit',
                'active' => ['admin.site.settings.*'],
                'perm' => 'site_settings.view',
            ],
        ],
    ],
];
