<?php

return [
    [
        'type' => 'accordion',
        'title' => 'E-Ticaret',
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
                'title' => 'Stok ve Varyantlar',
                'route' => 'admin.ecommerce.inventory.index',
                'active' => ['admin.ecommerce.inventory.*'],
                'perm' => 'ecommerce_inventory.view',
            ],
            [
                'title' => 'Kupon ve Kampanyalar',
                'route' => 'admin.ecommerce.coupons.index',
                'active' => ['admin.ecommerce.coupons.*'],
                'perm' => 'ecommerce_coupons.view',
            ],
            [
                'title' => 'Fatura ve Belgeler',
                'route' => 'admin.ecommerce.invoices.index',
                'active' => ['admin.ecommerce.invoices.*'],
                'perm' => 'ecommerce_invoices.view',
            ],
            [
                'title' => 'Ödeme Entegrasyonları',
                'route' => 'admin.site.payments.index',
                'active' => ['admin.site.payments.*'],
                'perm' => 'site_payments.view',
            ],
            [
                'title' => 'Webhook Kayıtları',
                'route' => 'admin.ecommerce.webhooks.index',
                'active' => ['admin.ecommerce.webhooks.*'],
                'perm' => 'ecommerce_webhooks.view',
            ],
        ],
    ],
];
