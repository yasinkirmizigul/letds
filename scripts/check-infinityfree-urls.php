<?php

// Offline simulation only. No application bootstrap, .env, DB or HTTP access.
require dirname(__DIR__).'/vendor/autoload.php';

use Illuminate\Config\Repository;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\UrlGenerator;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

$root = dirname(__DIR__);
$origin = 'https://probablue.freedev.app';
$app = new Application($root);
$request = Request::create($origin.'/login', 'GET', [], [], [], [
    'SCRIPT_NAME' => '/public/index.php',
    'SCRIPT_FILENAME' => $root.'/public/index.php',
    'PHP_SELF' => '/public/index.php',
    'REQUEST_URI' => '/login',
    'HTTPS' => 'on',
    'SERVER_PORT' => 443,
]);
$routes = new RouteCollection;
$routes->add((new Route(['GET'], 'login', fn () => null))->name('login'));
$urls = new UrlGenerator($routes, $request);
$app->instance('request', $request);
$app->instance('url', $urls);
$app->instance('config', new Repository(['app' => ['url' => $origin]]));
$adapter = new LocalFilesystemAdapter($root.'/storage/app/public');
$disk = new FilesystemAdapter(new Filesystem($adapter), $adapter, ['url' => $origin.'/storage']);
$viteTags = (string) (new Vite)(['resources/css/app.css', 'resources/js/admin/app.js']);
$checks = [
    'rewritten_script_has_empty_base_url' => $request->getBaseUrl() === '',
    'asset' => $urls->asset('assets/admin/js/core.bundle.js') === $origin.'/assets/admin/js/core.bundle.js',
    'url' => $urls->to('/login') === $origin.'/login',
    'route' => $urls->route('login') === $origin.'/login',
    'public_storage' => $disk->url('example.webp') === $origin.'/storage/example.webp',
    'vite_uses_domain_build' => str_contains($viteTags, $origin.'/build/assets/'),
    'vite_has_no_public_prefix' => !str_contains($viteTags, $origin.'/public/'),
    'vite_has_no_dev_server' => !str_contains($viteTags, ':5173'),
];
echo json_encode(['checks' => $checks, 'all_passed' => !in_array(false, $checks, true)], JSON_PRETTY_PRINT).PHP_EOL;
exit(in_array(false, $checks, true) ? 1 : 0);
