<?php

return [
    [
        'type' => 'single',
        'title' => 'Mesajlar',
        'icon' => 'ki-filled ki-messages text-lg',
        'route' => 'admin.messages.index',
        'active' => ['admin.messages.*'],
        'guard' => 'admin',
        'style' => 'margin-inline-start: -5px;',
    ],
];
