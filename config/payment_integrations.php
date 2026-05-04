<?php

return [
    'environment_options' => [
        'sandbox' => 'Test Ortamı',
        'live' => 'Canlı Ortam',
    ],

    'payment_method_options' => [
        'card' => 'Kart ile ödeme',
        '3d_secure' => '3D Güvenli Ödeme',
        'installment' => 'Taksit',
        'link' => 'Ödeme linki',
        'wallet' => 'Cüzdan / Ödeme Ekranı',
        'bank_transfer' => 'Havale / EFT',
    ],

    'currency_options' => [
        'TRY' => 'TRY - Türk Lirası',
        'USD' => 'USD - Amerikan Doları',
        'EUR' => 'EUR - Euro',
        'GBP' => 'GBP - Sterlin',
    ],

    'providers' => [
        'iyzico' => [
            'label' => 'iyzico',
            'description' => 'Türkiye odaklı kart, 3D Secure ve taksit akışları için yaygın ödeme altyapısı.',
            'integration_type' => 'virtual_pos',
            'default_environment' => 'sandbox',
            'recommended_payment_methods' => ['card', '3d_secure', 'installment', 'link'],
            'fields' => [
                [
                    'key' => 'api_key',
                    'label' => 'API Anahtarı',
                    'required' => true,
                    'secret' => true,
                    'placeholder' => 'sandbox-... veya live-...',
                ],
                [
                    'key' => 'secret_key',
                    'label' => 'Gizli Anahtar',
                    'required' => true,
                    'secret' => true,
                    'placeholder' => 'Gizli anahtar',
                ],
                [
                    'key' => 'base_url',
                    'label' => 'API Temel Adresi',
                    'type' => 'url',
                    'required' => true,
                    'default' => 'https://sandbox-api.iyzipay.com',
                ],
            ],
        ],
        'paytr' => [
            'label' => 'PayTR',
            'description' => 'Sanal POS, gömülü ödeme ekranı ve ödeme linki akışları için sık kullanılan yerel sağlayıcı.',
            'integration_type' => 'virtual_pos',
            'default_environment' => 'sandbox',
            'recommended_payment_methods' => ['card', '3d_secure', 'installment', 'link'],
            'fields' => [
                [
                    'key' => 'merchant_id',
                    'label' => 'Üye İşyeri No',
                    'required' => true,
                ],
                [
                    'key' => 'merchant_key',
                    'label' => 'Üye İşyeri Anahtarı',
                    'required' => true,
                    'secret' => true,
                ],
                [
                    'key' => 'merchant_salt',
                    'label' => 'Üye İşyeri Güvenlik Kodu',
                    'required' => true,
                    'secret' => true,
                ],
            ],
        ],
        'param' => [
            'label' => 'Param',
            'description' => 'Kurumsal sanal POS ve bayi ödeme senaryoları için kullanılan altyapı.',
            'integration_type' => 'virtual_pos',
            'default_environment' => 'sandbox',
            'recommended_payment_methods' => ['card', '3d_secure', 'installment'],
            'fields' => [
                [
                    'key' => 'client_code',
                    'label' => 'Müşteri Kodu',
                    'required' => true,
                ],
                [
                    'key' => 'client_username',
                    'label' => 'Müşteri Kullanıcı Adı',
                    'required' => true,
                ],
                [
                    'key' => 'client_password',
                    'label' => 'Müşteri Şifresi',
                    'required' => true,
                    'secret' => true,
                ],
                [
                    'key' => 'guid',
                    'label' => 'GUID',
                    'required' => true,
                    'secret' => true,
                ],
            ],
        ],
        'sipay' => [
            'label' => 'Sipay',
            'description' => 'Modern ödeme ekranı ve tahsilat senaryolarında kullanılan ödeme yönetim altyapısı.',
            'integration_type' => 'payment_gateway',
            'default_environment' => 'sandbox',
            'recommended_payment_methods' => ['card', '3d_secure', 'installment', 'link'],
            'fields' => [
                [
                    'key' => 'merchant_key',
                    'label' => 'Üye İşyeri Anahtarı',
                    'required' => true,
                    'secret' => true,
                ],
                [
                    'key' => 'merchant_secret',
                    'label' => 'Üye İşyeri Gizli Anahtarı',
                    'required' => true,
                    'secret' => true,
                ],
                [
                    'key' => 'api_base_url',
                    'label' => 'API Temel Adresi',
                    'type' => 'url',
                    'required' => true,
                    'default' => 'https://provisioning.sipay.com.tr/ccpayment/api',
                ],
            ],
        ],
        'stripe' => [
            'label' => 'Stripe',
            'description' => 'Global kart, ödeme ekranı ve ödeme bildirimi akışları için güçlü bir ödeme platformu.',
            'integration_type' => 'payment_gateway',
            'default_environment' => 'sandbox',
            'recommended_payment_methods' => ['card', 'wallet', 'link'],
            'fields' => [
                [
                    'key' => 'publishable_key',
                    'label' => 'Yayınlanabilir Anahtar',
                    'required' => true,
                ],
                [
                    'key' => 'secret_key',
                    'label' => 'Gizli Anahtar',
                    'required' => true,
                    'secret' => true,
                ],
                [
                    'key' => 'webhook_secret',
                    'label' => 'Ödeme Bildirimi Gizli Anahtarı',
                    'required' => false,
                    'secret' => true,
                ],
            ],
        ],
        'paypal' => [
            'label' => 'PayPal',
            'description' => 'Uluslararası ödeme ve cüzdan ağırlıklı senaryolar için tercih edilen çözüm.',
            'integration_type' => 'wallet',
            'default_environment' => 'sandbox',
            'recommended_payment_methods' => ['wallet', 'link'],
            'fields' => [
                [
                    'key' => 'client_id',
                    'label' => 'Müşteri No',
                    'required' => true,
                ],
                [
                    'key' => 'client_secret',
                    'label' => 'Müşteri Gizli Anahtarı',
                    'required' => true,
                    'secret' => true,
                ],
                [
                    'key' => 'api_base_url',
                    'label' => 'API Temel Adresi',
                    'type' => 'url',
                    'required' => true,
                    'default' => 'https://api-m.sandbox.paypal.com',
                ],
            ],
        ],
        'bank_transfer' => [
            'label' => 'Banka Havalesi',
            'description' => 'Sanal POS yanında manuel havale/EFT bilgilerini de merkezi olarak yönetebilirsin.',
            'integration_type' => 'bank_transfer',
            'default_environment' => 'live',
            'recommended_payment_methods' => ['bank_transfer'],
            'fields' => [
                [
                    'key' => 'bank_name',
                    'label' => 'Banka Adı',
                    'required' => true,
                ],
                [
                    'key' => 'account_holder',
                    'label' => 'Hesap Sahibi',
                    'required' => true,
                ],
                [
                    'key' => 'iban',
                    'label' => 'IBAN',
                    'required' => true,
                ],
                [
                    'key' => 'branch_code',
                    'label' => 'Şube Kodu',
                    'required' => false,
                ],
            ],
        ],
    ],
];
