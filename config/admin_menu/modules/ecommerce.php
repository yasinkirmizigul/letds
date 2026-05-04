<?php

return [
    [
        'type' => 'accordion',
        'title' => 'E-Ticaret',
        'icon' => 'ki-filled ki-basket text-lg',
        'permAny' => [
            'ecommerce_orders.view',
            'products.view',
            'site_payments.view',
        ],
        'children' => [
            [
                'title' => 'Sipariş Yönetimi',
                'route' => 'admin.ecommerce.orders.index',
                'active' => ['admin.ecommerce.orders.*'],
                'perm' => 'ecommerce_orders.view',
            ],
            [
                'title' => 'Ürün Kataloğu',
                'route' => 'admin.products.index',
                'active' => ['admin.products.*'],
                'perm' => 'products.view',
            ],
            [
                'title' => 'Ödeme Entegrasyonları',
                'route' => 'admin.site.payments.index',
                'active' => ['admin.site.payments.*'],
                'perm' => 'site_payments.view',
            ],
        ],
    ],
];
