<?php

return [
    'environment_options' => [
        'sandbox' => 'Test / Sandbox',
        'live' => 'Canlı / Production',
    ],

    'payment_method_options' => [
        'card' => 'Kart ile ödeme',
        '3d_secure' => '3D Secure',
        'installment' => 'Taksit',
        'link' => 'Ödeme linki',
        'wallet' => 'Cüzdan / Checkout',
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
                    'label' => 'API Key',
                    'required' => true,
                    'secret' => true,
                    'placeholder' => 'sandbox-... veya live-...',
                ],
                [
                    'key' => 'secret_key',
                    'label' => 'Secret Key',
                    'required' => true,
                    'secret' => true,
                    'placeholder' => 'Gizli anahtar',
                ],
                [
                    'key' => 'base_url',
                    'label' => 'API Base URL',
                    'type' => 'url',
                    'required' => true,
                    'default' => 'https://sandbox-api.iyzipay.com',
                ],
            ],
        ],
        'paytr' => [
            'label' => 'PayTR',
            'description' => 'Sanal POS, iframe ve ödeme linki akışları için sık kullanılan yerel sağlayıcı.',
            'integration_type' => 'virtual_pos',
            'default_environment' => 'sandbox',
            'recommended_payment_methods' => ['card', '3d_secure', 'installment', 'link'],
            'fields' => [
                [
                    'key' => 'merchant_id',
                    'label' => 'Merchant ID',
                    'required' => true,
                ],
                [
                    'key' => 'merchant_key',
                    'label' => 'Merchant Key',
                    'required' => true,
                    'secret' => true,
                ],
                [
                    'key' => 'merchant_salt',
                    'label' => 'Merchant Salt',
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
                    'label' => 'Client Code',
                    'required' => true,
                ],
                [
                    'key' => 'client_username',
                    'label' => 'Client Username',
                    'required' => true,
                ],
                [
                    'key' => 'client_password',
                    'label' => 'Client Password',
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
            'description' => 'Modern checkout ve tahsilat senaryolarında kullanılan ödeme orkestrasyon altyapısı.',
            'integration_type' => 'payment_gateway',
            'default_environment' => 'sandbox',
            'recommended_payment_methods' => ['card', '3d_secure', 'installment', 'link'],
            'fields' => [
                [
                    'key' => 'merchant_key',
                    'label' => 'Merchant Key',
                    'required' => true,
                    'secret' => true,
                ],
                [
                    'key' => 'merchant_secret',
                    'label' => 'Merchant Secret',
                    'required' => true,
                    'secret' => true,
                ],
                [
                    'key' => 'api_base_url',
                    'label' => 'API Base URL',
                    'type' => 'url',
                    'required' => true,
                    'default' => 'https://provisioning.sipay.com.tr/ccpayment/api',
                ],
            ],
        ],
        'stripe' => [
            'label' => 'Stripe',
            'description' => 'Global kart, checkout ve webhook akışları için güçlü bir ödeme platformu.',
            'integration_type' => 'payment_gateway',
            'default_environment' => 'sandbox',
            'recommended_payment_methods' => ['card', 'wallet', 'link'],
            'fields' => [
                [
                    'key' => 'publishable_key',
                    'label' => 'Publishable Key',
                    'required' => true,
                ],
                [
                    'key' => 'secret_key',
                    'label' => 'Secret Key',
                    'required' => true,
                    'secret' => true,
                ],
                [
                    'key' => 'webhook_secret',
                    'label' => 'Webhook Secret',
                    'required' => false,
                    'secret' => true,
                ],
            ],
        ],
        'paypal' => [
            'label' => 'PayPal',
            'description' => 'Uluslararası ödeme ve wallet ağırlıklı senaryolar için tercih edilen çözüm.',
            'integration_type' => 'wallet',
            'default_environment' => 'sandbox',
            'recommended_payment_methods' => ['wallet', 'link'],
            'fields' => [
                [
                    'key' => 'client_id',
                    'label' => 'Client ID',
                    'required' => true,
                ],
                [
                    'key' => 'client_secret',
                    'label' => 'Client Secret',
                    'required' => true,
                    'secret' => true,
                ],
                [
                    'key' => 'api_base_url',
                    'label' => 'API Base URL',
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
