<?php

return [
    'access' => [
        'enabled' => (bool) env('DEMO_ACCESS_ENABLED', false),
        'username' => env('DEMO_ACCESS_USERNAME', 'demo'),
        'password' => env('DEMO_ACCESS_PASSWORD'),
    ],
];
