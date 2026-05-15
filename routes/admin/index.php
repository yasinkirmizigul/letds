<?php

$routeFiles = [
    'dashboard.php',
    'access.php',
    'content.php',
    'catalog.php',
    'media.php',
    'system.php',
    'profile.php',
    'editor.php',
];

foreach ($routeFiles as $routeFile) {
    require __DIR__ . '/' . $routeFile;
}

// [ADMIN_MODULE_ROUTES:START]
$__adminModuleDir = __DIR__ . '/modules';
if (is_dir($__adminModuleDir)) {
    foreach (glob($__adminModuleDir . '/*.php') ?: [] as $__f) {
        require $__f;
    }
}
// [ADMIN_MODULE_ROUTES:END]
