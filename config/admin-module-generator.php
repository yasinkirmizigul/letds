<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Base paths
    |--------------------------------------------------------------------------
    */
    'admin_route_prefix' => 'admin',
    'routes_modules_path' => base_path('routes/admin/modules'),
    'menu_modules_path'   => base_path('config/admin_menu/modules'),

    /*
    |--------------------------------------------------------------------------
    | Presets (Kurumsal)
    |--------------------------------------------------------------------------
    | - content: basit içerik modülü (title/slug/meta/status/featured/content)
    | - product: ileride e-ticaret ürün şablonu (sku/price/stock/...)
    |
    | Varsayılan preset: content
    */
    'default_preset' => env('ADMIN_MODULE_DEFAULT_PRESET', 'content'),

    'presets' => [
        'content' => [
            'label' => 'Content Module',
            'stubs_path' => base_path('stubs/admin-module/presets/content'),
        ],
        'product' => [
            'label' => 'Product Module (E-commerce)',
            'stubs_path' => base_path('stubs/admin-module/presets/product'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Namespaces
    |--------------------------------------------------------------------------
    */
    'controller_namespace' => 'App\\Http\\Controllers\\Admin',
    'request_namespace'    => 'App\\Http\\Requests\\Admin',
    'policy_namespace'     => 'App\\Policies\\Admin',
    'model_namespace'      => 'App\\Models\\Admin',

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    | driver: 'spatie' or 'custom'
    | - spatie: uses Spatie\Permission\Models\Permission if exists
    | - custom: uses App\Models\Permission if exists
    | Seeder is generated with runtime checks so it won't crash if class missing.
    */
    'permission' => [
        'driver' => env('ADMIN_MODULE_PERMISSION_DRIVER', 'spatie'),
        'guard_name' => env('ADMIN_MODULE_PERMISSION_GUARD', 'web'),
        'name_prefix' => 'admin', // e.g. admin.products.view
    ],

    /*
    |--------------------------------------------------------------------------
    | Module defaults
    |--------------------------------------------------------------------------
    */
    'defaults' => [

        // Formlar varsayılan olarak AJAX save kullansın mı
        'ajax_save' => false,

        // Aynı anda kaç featured olabilir (0 = sınırsız)
        'featured_limit' => 1,

        // Status seçenekleri (badge + label)
        'statuses' => [
            'draft' => [
                'label' => 'Taslak',
                'badge' => 'kt-badge kt-badge-sm kt-badge-light',
            ],
            'published' => [
                'label' => 'Yayınlandı',
                'badge' => 'kt-badge kt-badge-sm kt-badge-success',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Marker-based patching (optional, safe)
    |--------------------------------------------------------------------------
    | If these markers exist in your files, generator will inject include lines.
    | If markers do NOT exist, generator will NOT touch those files.
    */
    'patching' => [
        'routes_web_file' => base_path('routes/web.php'),
        'routes_marker'   => '// [ADMIN_MODULE_ROUTES]',
        'menu_file'       => base_path('config/admin_menu.php'),
        'menu_marker'     => '// [ADMIN_MODULE_MENU]',
    ],
];
