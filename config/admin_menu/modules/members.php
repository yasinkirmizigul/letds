<?php

return [
    [
        'type' => 'single',
        'title' => 'Üyelikler',
        'icon' => 'ki-filled ki-users text-lg',
        'route' => 'admin.members.index',
        'active' => ['admin.members.*'],
        'perm' => 'members.view',
        'style' => 'margin-inline-start: -5px;',
    ],
];
