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
        'type' => 'accordion',
        'title' => 'İçerik ve Vitrin',
        'icon' => 'ki-filled ki-element-11 text-lg',
        'permAny' => [
            'blog.view',
            'projects.view',
            'categories.view',
            'galleries.view',
            'media.view',
        ],
        'children' => [
            [
                'title' => 'Yazılar',
                'route' => 'admin.blog.index',
                'active' => ['admin.blog.*'],
                'perm' => 'blog.view',
            ],
            [
                'title' => 'Projeler',
                'route' => 'admin.projects.index',
                'active' => ['admin.projects.*'],
                'perm' => 'projects.view',
            ],
            [
                'title' => 'Kategoriler',
                'route' => 'admin.categories.index',
                'active' => ['admin.categories.*'],
                'perm' => 'categories.view',
            ],
            [
                'title' => 'Galeriler',
                'route' => 'admin.galleries.index',
                'active' => ['admin.galleries.*'],
                'perm' => 'galleries.view',
            ],
            [
                'title' => 'Medya Kütüphanesi',
                'route' => 'admin.media.index',
                'active' => ['admin.media.*'],
                'perm' => 'media.view',
            ],
        ],
    ],
];

$appointmentMenu = [
    [
        'type' => 'accordion',
        'title' => 'Randevu Operasyonu',
        'icon' => 'ki-filled ki-calendar-8 text-lg',
        'permAny' => ['appointments.view', 'appointments.update'],
        'children' => [
            [
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
        ],
    ],
];

$userMenu = [
    [
        'type' => 'accordion',
        'title' => 'Kullanıcı ve Yetki',
        'icon' => 'ki-filled ki-profile-circle text-lg',
        'permAny' => ['users.view', 'roles.view', 'permissions.view'],
        'children' => [
            [
                'title' => 'Kullanıcı Listesi',
                'route' => 'admin.users.index',
                'active' => ['admin.users.*'],
                'perm' => 'users.view',
            ],
            [
                'title' => 'Roller',
                'route' => 'admin.roles.index',
                'active' => ['admin.roles.*'],
                'perm' => 'roles.view',
            ],
            [
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
        'type' => 'single',
        'title' => 'Sistem Kayıtları',
        'icon' => 'ki-filled ki-fingerprint-scanning text-lg',
        'route' => 'admin.audit-logs.index',
        'active' => ['admin.audit-logs.*'],
        'perm' => 'audit-logs.view',
        'style' => 'margin-inline-start: -5px;',
    ],
    [
        'type' => 'single',
        'title' => 'Silinenler',
        'icon' => 'ki-filled ki-trash text-lg',
        'route' => 'admin.trash.index',
        'active' => ['admin.trash.*'],
        'perm' => 'trash.view',
        'style' => 'margin-inline-start: -5px;',
    ],
];

$menu = array_merge(
    [
        [
            'type' => 'single',
            'title' => 'Kontrol Paneli',
            'icon' => 'ki-filled ki-element-11 text-lg',
            'route' => 'admin.dashboard',
            'active' => ['admin.dashboard', 'admin.dashboard.*'],
            'guard' => 'admin',
            'style' => 'margin-inline-start: -5px;',
        ],
    ],
    $moduleItems['ecommerce.php'],
    $moduleItems['messages.php'],
    $moduleItems['notifications.php'],
    $appointmentMenu,
    $moduleItems['members.php'],
    $contentMenu,
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
