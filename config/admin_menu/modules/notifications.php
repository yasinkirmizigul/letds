<?php

return [
    [
        'type' => 'single',
        'title' => 'Bildirim Merkezi',
        'icon' => 'ki-filled ki-notification-status text-lg',
        'route' => 'admin.notifications.index',
        'active' => ['admin.notifications.*'],
        'perm' => 'notifications.view',
        'style' => 'margin-inline-start: -5px;',
    ],
];
