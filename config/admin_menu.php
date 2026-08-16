<?php

$modulesDir = __DIR__ . '/admin_menu/modules';
$moduleItems = [];
$knownModuleFiles = [
    'ecommerce.php',
    'messages.php',
    'notifications.php',
    'members.php',
    'site_cms.php',
];

foreach ($knownModuleFiles as $file) {
    $path = $modulesDir . '/' . $file;
    $moduleItems[$file] = is_file($path) ? (require $path) : [];
}

$contentMenu = [
    [
        'key' => 'content',
        'type' => 'accordion',
        'title' => 'İçerik ve Vitrin',
        'icon' => 'ki-filled ki-element-11 text-lg',
        'permAny' => [
            'site_pages.view',
            'site_faqs.view',
            'site_counters.view',
            'site_navigation.view',
            'site_homepage.view',
            'home_sliders.view',
            'blog.view',
            'projects.view',
            'categories.view',
            'galleries.view',
            'media.view',
        ],
        'children' => [
            [
                'key' => 'content.homepage',
                'title' => 'Ana Sayfa Yönetimi',
                'route' => 'admin.site.homepage.edit',
                'active' => ['admin.site.homepage.*'],
                'perm' => 'site_homepage.view',
            ],
            [
                'key' => 'content.homepage-sections',
                'title' => 'Ana Sayfa Bölümleri',
                'route' => 'admin.site.homepage-sections.index',
                'active' => ['admin.site.homepage-sections.*'],
                'perm' => 'site_homepage.view',
            ],
            [
                'key' => 'content.sliders',
                'title' => 'Ana Sayfa Slider',
                'route' => 'admin.site.sliders.index',
                'active' => ['admin.site.sliders.*'],
                'perm' => 'home_sliders.view',
            ],
            [
                'key' => 'content.pages',
                'title' => 'Sayfalar',
                'route' => 'admin.site.pages.index',
                'active' => ['admin.site.pages.*'],
                'perm' => 'site_pages.view',
            ],
            [
                'key' => 'content.blog',
                'title' => 'Yazılar',
                'route' => 'admin.blog.index',
                'active' => ['admin.blog.*'],
                'perm' => 'blog.view',
            ],
            [
                'key' => 'content.projects',
                'title' => 'Projeler',
                'route' => 'admin.projects.index',
                'active' => ['admin.projects.*'],
                'perm' => 'projects.view',
            ],
            [
                'key' => 'content.categories',
                'title' => 'Kategoriler',
                'route' => 'admin.categories.index',
                'active' => ['admin.categories.*'],
                'perm' => 'categories.view',
            ],
            [
                'key' => 'content.galleries',
                'title' => 'Galeriler',
                'route' => 'admin.galleries.index',
                'active' => ['admin.galleries.*'],
                'perm' => 'galleries.view',
            ],
            [
                'key' => 'content.media',
                'title' => 'Medya Kütüphanesi',
                'route' => 'admin.media.index',
                'active' => ['admin.media.*'],
                'perm' => 'media.view',
            ],
            [
                'key' => 'content.faqs',
                'title' => 'Sıkça Sorulan Sorular',
                'route' => 'admin.site.faqs.index',
                'active' => ['admin.site.faqs.*'],
                'perm' => 'site_faqs.view',
            ],
            [
                'key' => 'content.counters',
                'title' => 'Sayaçlar',
                'route' => 'admin.site.counters.index',
                'active' => ['admin.site.counters.*'],
                'perm' => 'site_counters.view',
            ],
            [
                'key' => 'content.navigation',
                'title' => 'Site Menüleri',
                'route' => 'admin.site.navigation.index',
                'active' => ['admin.site.navigation.*'],
                'perm' => 'site_navigation.view',
            ],
        ],
    ],
];

$operationsMenu = [
    [
        'key' => 'operations',
        'type' => 'accordion',
        'title' => 'Operasyon',
        'icon' => 'ki-filled ki-abstract-22 text-lg',
        'permAny' => [
            'messages.view',
            'appointments.view',
            'appointments.update',
            'members.view',
            'service_reviews.view',
            'notifications.view',
        ],
        'children' => [
            [
                'key' => 'operations.messages',
                'title' => 'Mesajlar',
                'route' => 'admin.messages.index',
                'active' => ['admin.messages.*'],
                'perm' => 'messages.view',
            ],
            [
                'key' => 'operations.appointments',
                'title' => 'Randevu Takvimi',
                'route' => 'admin.appointments.calendar',
                'active' => [
                    'admin.appointments.calendar',
                    'admin.appointments.calendar.*',
                    'admin.appointments.store',
                    'admin.appointments.show',
                    'admin.appointments.history',
                    'admin.appointments.cancel',
                    'admin.appointments.resize',
                    'admin.appointments.transfer',
                    'admin.appointments.blocks.*',
                ],
                'perm' => 'appointments.view',
            ],
            [
                'key' => 'operations.appointment_settings',
                'title' => 'Randevu Ayarları',
                'route' => 'admin.appointments.settings',
                'active' => [
                    'admin.appointments.settings',
                    'admin.appointments.providers.*',
                    'admin.appointments.blackouts.*',
                    'admin.appointments.availability',
                ],
                'perm' => 'appointments.update',
            ],
            [
                'key' => 'operations.members',
                'title' => 'Üyelikler',
                'route' => 'admin.members.index',
                'active' => ['admin.members.*'],
                'perm' => 'members.view',
            ],
            [
                'key' => 'operations.service_reviews',
                'title' => 'Değerlendirmeler',
                'route' => 'admin.service-reviews.index',
                'active' => ['admin.service-reviews.*'],
                'perm' => 'service_reviews.view',
            ],
            [
                'key' => 'operations.notifications',
                'title' => 'Bildirim Merkezi',
                'route' => 'admin.notifications.index',
                'active' => ['admin.notifications.*'],
                'perm' => 'notifications.view',
            ],
        ],
    ],
];

$userMenu = [
    [
        'key' => 'access',
        'type' => 'accordion',
        'title' => 'Kullanıcı ve Yetki',
        'icon' => 'ki-filled ki-profile-circle text-lg',
        'permAny' => ['users.view', 'roles.view', 'permissions.view'],
        'children' => [
            [
                'key' => 'access.users',
                'title' => 'Kullanıcı Listesi',
                'route' => 'admin.users.index',
                'active' => ['admin.users.*'],
                'perm' => 'users.view',
            ],
            [
                'key' => 'access.roles',
                'title' => 'Roller',
                'route' => 'admin.roles.index',
                'active' => ['admin.roles.*'],
                'perm' => 'roles.view',
            ],
            [
                'key' => 'access.permissions',
                'title' => 'Yetkiler',
                'route' => 'admin.permissions.index',
                'active' => ['admin.permissions.*'],
                'perm' => 'permissions.view',
            ],
        ],
    ],
];

$systemMenu = [
    [
        'key' => 'system',
        'type' => 'accordion',
        'title' => 'Sistem',
        'icon' => 'ki-filled ki-setting-2 text-lg',
        'permAny' => ['audit-logs.view', 'trash.view'],
        'children' => [
            [
                'key' => 'system.audit_logs',
                'title' => 'Sistem Kayıtları',
                'route' => 'admin.audit-logs.index',
                'active' => ['admin.audit-logs.*'],
                'perm' => 'audit-logs.view',
            ],
            [
                'key' => 'system.trash',
                'title' => 'Silinenler',
                'route' => 'admin.trash.index',
                'active' => ['admin.trash.*'],
                'perm' => 'trash.view',
            ],
            [
                'key' => 'system.demo_data',
                'title' => 'Örnek Veri Fabrikası',
                'route' => 'admin.demo-data.index',
                'active' => ['admin.demo-data.*'],
                'guard' => 'superadmin',
            ],
        ],
    ],
];

$menu = array_merge(
    [
        [
            'key' => 'dashboard',
            'type' => 'single',
            'title' => 'Kontrol Paneli',
            'icon' => 'ki-filled ki-element-11 text-lg',
            'route' => 'admin.dashboard',
            'active' => ['admin.dashboard', 'admin.dashboard.*'],
            'guard' => 'admin',
            'style' => 'margin-inline-start: -5px;',
        ],
    ],
    $operationsMenu,
    $contentMenu,
    $moduleItems['ecommerce.php'],
    $moduleItems['site_cms.php'],
    $userMenu,
    $systemMenu
);

foreach (glob($modulesDir . '/*.php') ?: [] as $path) {
    if (in_array(basename($path), $knownModuleFiles, true)) {
        continue;
    }

    $items = require $path;
    if (is_array($items)) {
        $menu = array_merge($menu, $items);
    }
}

return $menu;
