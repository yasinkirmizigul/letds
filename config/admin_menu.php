<?php

return [
    [
        'type'   => 'single',
        'title'  => 'Dashboard',
        'icon'   => 'ki-filled ki-element-11 text-lg',
        'route'  => 'admin.dashboard',
        'active' => ['admin.dashboard'],
        'guard'  => 'admin',                 // @admin kullan
    ],

    [
        'type'   => 'accordion',
        'title'  => 'Blog',
        'icon'   => 'ki-filled ki-book text-lg',
        'active' => ['admin.blog.*'],
        'permAny'=> ['blog.view'],           // parent görünme şartı
        'children' => [
            [
                'title'  => 'Yazılar',
                'route'  => 'admin.blog.index',
                'active' => ['admin.blog.*'],
                'perm'   => 'blog.view',
            ],
        ],
    ],

    [
        'type'   => 'accordion',
        'title'  => 'Medya',
        'icon'   => 'ki-filled ki-screen text-lg',
        'active' => ['admin.media.*'],
        'permAny'=> ['media.view'],
        'children' => [
            [
                'title'  => 'Medyalar',
                'route'  => 'admin.media.index',
                'active' => ['admin.media.*'],
                'perm'   => 'media.view',
            ],
        ],
    ],

    [
        'type'   => 'accordion',
        'title'  => 'Kategoriler',
        'icon'   => 'ki-filled ki-document text-lg',
        'active' => ['admin.categories.*'],
        'permAny'=> ['category.view'],
        'children' => [
            [
                'title'  => 'Kategoriler',
                'route'  => 'admin.categories.index',
                'active' => ['admin.categories.*'],
                'perm'   => 'category.view',
            ],
        ],
    ],

    [
        'type'   => 'accordion',
        'title'  => 'Kullanıcılar',
        'icon'   => 'ki-filled ki-profile-circle text-lg',
        'active' => ['admin.users.*','admin.roles.*','admin.permissions.*'],
        'permAny'=> ['users.view','roles.view','permissions.view'],
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
    ],
];
