<?php

$routeFiles = [
    'member/auth.php',
    'member/appointments.php',
    'member/account.php',
    'content.php',
];

foreach ($routeFiles as $routeFile) {
    require __DIR__ . '/' . $routeFile;
}

// [SITE_MODULE_ROUTES:START]
$__siteModuleDir = __DIR__ . '/modules';
if (is_dir($__siteModuleDir)) {
    foreach (glob($__siteModuleDir . '/*.php') ?: [] as $__f) {
        require $__f;
    }
}
// [SITE_MODULE_ROUTES:END]
