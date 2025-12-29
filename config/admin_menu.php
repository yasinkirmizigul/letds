<?php

return [
    [
        'type'  => 'single',
        'title' => 'Dashboard',
        'icon'  => 'ki-filled ki-element-11 text-lg',
        'route' => 'admin.dashboard',
        'active'=> ['admin.dashboard'],
        'guard' => 'admin', // @admin
        'style' => 'margin-inline-start: -5px;',
    ],

    [
        'type'     => 'accordion',
        'title'    => 'Blog',
        'icon'     => 'ki-filled ki-book text-lg',
        'perm'     => 'blog.view',
        'children' => [
            [
                'title'  => 'Yazılar',
                'route'  => 'admin.blog.index',
                'active' => ['admin.blog.*'],
                'perm'   => 'blog.view',
            ],
        ],
        'style' => '',
    ],

    [
        'type'     => 'accordion',
        'title'    => 'Medya',
        'icon'     => 'ki-filled ki-screen text-lg',
        'perm'     => 'media.view',
        'children' => [
            [
                'title'  => 'Medyalar',
                'route'  => 'admin.media.index',
                'active' => ['admin.media.*'],
                'perm'   => 'media.view',
            ],
        ],
        'style' => '',
    ],

    // ✅ NEW: Galleries
    [
        'type'     => 'accordion',
        'title'    => 'Galeri',
        'icon'     => 'ki-filled ki-picture text-lg',
        'perm'     => 'gallery.view',
        'children' => [
            [
                'title'  => 'Galeriler',
                'route'  => 'admin.galleries.index',
                'active' => ['admin.galleries.*'],
                'perm'   => 'gallery.view',
            ],
        ],
        'style' => '',
    ],

    [
        'type'     => 'accordion',
        'title'    => 'Kategori',
        'icon'     => 'ki-filled ki-document text-lg',
        'perm'     => 'category.view',
        'children' => [
            [
                'title'  => 'Kategoriler',
                'route'  => 'admin.categories.index',
                'active' => ['admin.categories.*'],
                'perm'   => 'category.view',
            ],
        ],
        'style' => '',
    ],

    [
        'type'     => 'accordion',
        'title'    => 'Kullanıcılar',
        'icon'     => 'ki-filled ki-profile-circle text-lg',
        'permAny'  => ['users.view', 'roles.view', 'permissions.view'],
        'children' => [
            [
                'title'  => 'Kullanıcı Listesi',
                'route'  => 'admin.users.index',
                'active' => ['admin.users.*'],
                'perm'   => 'users.view',
            ],
            [
                'title'  => 'Roller',
                'route'  => 'admin.roles.index',
                'active' => ['admin.roles.*'],
                'perm'   => 'roles.view',
            ],
            [
                'title'  => 'İzinler',
                'route'  => 'admin.permissions.index',
                'active' => ['admin.permissions.*'],
                'perm'   => 'permissions.view',
            ],
        ],
        'style' => '',
    ],

    [
        'type'  => 'single',
        'title' => 'Loglar',
        'icon'  => 'ki-filled ki-fingerprint-scanning text-lg',
        'route' => 'admin.audit-logs.index',
        'active'=> ['admin.audit-logs'],
        'style' => 'margin-inline-start: -5px;',
    ],

    [
        'type'  => 'single',
        'title' => 'Silinenler',
        'icon'  => 'ki-filled ki-trash text-lg',
        'route' => 'admin.trash.index',
        'active'=> ['admin.trash'],
        'style' => 'margin-inline-start: -5px;',
    ],
];
