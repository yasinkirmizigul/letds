<?php

use App\Http\Controllers\Site\Cms\HomeController;
use App\Http\Controllers\Site\Cms\PageController;
use App\Http\Controllers\Site\ContactMessageController;
use Illuminate\Support\Facades\Route;

Route::middleware('site.locale')->group(function () {
    Route::get('/', [HomeController::class, 'index'])
        ->name('site.home');

    Route::get('/iletisim', [ContactMessageController::class, 'create'])
        ->name('site.contact-messages.create');

    Route::post('/iletisim', [ContactMessageController::class, 'store'])
        ->middleware('throttle:12,1')
        ->name('site.contact-messages.store');

    Route::prefix('{locale}')
        ->where(['locale' => '[A-Za-z]{2}(?:-[A-Za-z]{2})?'])
        ->group(function () {
            Route::get('/', [HomeController::class, 'index'])
                ->name('site.home.localized');

            Route::get('/{slug}', [PageController::class, 'show'])
                ->name('site.pages.show.localized');
        });

    Route::get('/{slug}', [PageController::class, 'show'])
        ->where('slug', '^(?!admin$|login$)[^/]+$')
        ->name('site.pages.show');
});
