<?php

return [
    [
        'key' => 'catalog',
        'type' => 'accordion',
        'title' => 'Satış ve Katalog',
        'icon' => 'ki-filled ki-basket text-lg',
        'permAny' => [
            'ecommerce_orders.view',
            'products.view',
            'ecommerce_inventory.view',
            'ecommerce_coupons.view',
            'ecommerce_invoices.view',
            'site_payments.view',
            'ecommerce_webhooks.view',
        ],
        'children' => [
            [
                'key' => 'catalog.products',
                'title' => 'Ürün Kataloğu',
                'route' => 'admin.products.index',
                'active' => ['admin.products.*'],
                'perm' => 'products.view',
            ],
            [
                'key' => 'catalog.orders',
                'title' => 'Siparişler',
                'route' => 'admin.ecommerce.orders.index',
                'active' => ['admin.ecommerce.orders.*'],
                'perm' => 'ecommerce_orders.view',
            ],
            [
                'key' => 'catalog.inventory',
                'title' => 'Stok ve Varyantlar',
                'route' => 'admin.ecommerce.inventory.index',
                'active' => ['admin.ecommerce.inventory.*'],
                'perm' => 'ecommerce_inventory.view',
            ],
            [
                'key' => 'catalog.coupons',
                'title' => 'Kupon ve Kampanyalar',
                'route' => 'admin.ecommerce.coupons.index',
                'active' => ['admin.ecommerce.coupons.*'],
                'perm' => 'ecommerce_coupons.view',
            ],
            [
                'key' => 'catalog.invoices',
                'title' => 'Fatura ve Belgeler',
                'route' => 'admin.ecommerce.invoices.index',
                'active' => ['admin.ecommerce.invoices.*'],
                'perm' => 'ecommerce_invoices.view',
            ],
            [
                'key' => 'catalog.payments',
                'title' => 'Ödeme Entegrasyonları',
                'route' => 'admin.site.payments.index',
                'active' => ['admin.site.payments.*'],
                'perm' => 'site_payments.view',
            ],
            [
                'key' => 'catalog.webhooks',
                'title' => 'Webhook Kayıtları',
                'route' => 'admin.ecommerce.webhooks.index',
                'active' => ['admin.ecommerce.webhooks.*'],
                'perm' => 'ecommerce_webhooks.view',
            ],
        ],
    ],
];
