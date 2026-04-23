<?php

use App\Http\Controllers\Admin\Site\ContentPageController;
use App\Http\Controllers\Admin\Site\FaqController;
use App\Http\Controllers\Admin\Site\HomeSliderController;
use App\Http\Controllers\Admin\Site\NavigationController;
use App\Http\Controllers\Admin\Site\SiteCounterController;
use App\Http\Controllers\Admin\Site\SiteLanguageController;
use App\Http\Controllers\Admin\Site\SiteSettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('site')->as('site.')->group(function () {
    Route::prefix('languages')->as('languages.')->group(function () {
        Route::get('/', [SiteLanguageController::class, 'index'])
            ->middleware('permission:site_languages.view')
            ->name('index');

        Route::post('/', [SiteLanguageController::class, 'store'])
            ->middleware('permission:site_languages.create')
            ->name('store');

        Route::put('/{siteLanguage}', [SiteLanguageController::class, 'update'])
            ->middleware('permission:site_languages.update')
            ->name('update');

        Route::patch('/{siteLanguage}/toggle-active', [SiteLanguageController::class, 'toggleActive'])
            ->middleware('permission:site_languages.update')
            ->name('toggleActive');

        Route::patch('/{siteLanguage}/make-default', [SiteLanguageController::class, 'makeDefault'])
            ->middleware('permission:site_languages.update')
            ->name('makeDefault');

        Route::delete('/{siteLanguage}', [SiteLanguageController::class, 'destroy'])
            ->middleware('permission:site_languages.delete')
            ->name('destroy');
    });

    Route::prefix('pages')->as('pages.')->group(function () {
        Route::get('/', [ContentPageController::class, 'index'])
            ->middleware('permission:site_pages.view')
            ->name('index');

        Route::get('/create', [ContentPageController::class, 'create'])
            ->middleware('permission:site_pages.create')
            ->name('create');

        Route::post('/', [ContentPageController::class, 'store'])
            ->middleware('permission:site_pages.create')
            ->name('store');

        Route::get('/{sitePage}/edit', [ContentPageController::class, 'edit'])
            ->middleware('permission:site_pages.update')
            ->name('edit');

        Route::put('/{sitePage}', [ContentPageController::class, 'update'])
            ->middleware('permission:site_pages.update')
            ->name('update');

        Route::patch('/{sitePage}/toggle-active', [ContentPageController::class, 'toggleActive'])
            ->middleware('permission:site_pages.update')
            ->name('toggleActive');

        Route::delete('/{sitePage}', [ContentPageController::class, 'destroy'])
            ->middleware('permission:site_pages.delete')
            ->name('destroy');
    });

    Route::prefix('faqs')->as('faqs.')->group(function () {
        Route::get('/', [FaqController::class, 'index'])
            ->middleware('permission:site_faqs.view')
            ->name('index');

        Route::post('/', [FaqController::class, 'store'])
            ->middleware('permission:site_faqs.create')
            ->name('store');

        Route::put('/{siteFaq}', [FaqController::class, 'update'])
            ->middleware('permission:site_faqs.update')
            ->name('update');

        Route::patch('/reorder', [FaqController::class, 'reorder'])
            ->middleware('permission:site_faqs.update')
            ->name('reorder');

        Route::delete('/{siteFaq}', [FaqController::class, 'destroy'])
            ->middleware('permission:site_faqs.delete')
            ->name('destroy');
    });

    Route::prefix('counters')->as('counters.')->group(function () {
        Route::get('/', [SiteCounterController::class, 'index'])
            ->middleware('permission:site_counters.view')
            ->name('index');

        Route::post('/', [SiteCounterController::class, 'store'])
            ->middleware('permission:site_counters.create')
            ->name('store');

        Route::put('/{siteCounter}', [SiteCounterController::class, 'update'])
            ->middleware('permission:site_counters.update')
            ->name('update');

        Route::patch('/reorder', [SiteCounterController::class, 'reorder'])
            ->middleware('permission:site_counters.update')
            ->name('reorder');

        Route::delete('/{siteCounter}', [SiteCounterController::class, 'destroy'])
            ->middleware('permission:site_counters.delete')
            ->name('destroy');
    });

    Route::prefix('navigation')->as('navigation.')->group(function () {
        Route::get('/', [NavigationController::class, 'index'])
            ->middleware('permission:site_navigation.view')
            ->name('index');

        Route::post('/', [NavigationController::class, 'store'])
            ->middleware('permission:site_navigation.create')
            ->name('store');

        Route::put('/{siteNavigationItem}', [NavigationController::class, 'update'])
            ->middleware('permission:site_navigation.update')
            ->name('update');

        Route::patch('/tree', [NavigationController::class, 'updateTree'])
            ->middleware('permission:site_navigation.update')
            ->name('tree');

        Route::patch('/{siteNavigationItem}/toggle-active', [NavigationController::class, 'toggleActive'])
            ->middleware('permission:site_navigation.update')
            ->name('toggleActive');

        Route::delete('/{siteNavigationItem}', [NavigationController::class, 'destroy'])
            ->middleware('permission:site_navigation.delete')
            ->name('destroy');
    });

    Route::get('/settings', [SiteSettingsController::class, 'edit'])
        ->middleware('permission:site_settings.view')
        ->name('settings.edit');

    Route::put('/settings', [SiteSettingsController::class, 'update'])
        ->middleware('permission:site_settings.update')
        ->name('settings.update');

    Route::prefix('sliders')->as('sliders.')->group(function () {
        Route::get('/', [HomeSliderController::class, 'index'])
            ->middleware('permission:home_sliders.view')
            ->name('index');

        Route::get('/create', [HomeSliderController::class, 'create'])
            ->middleware('permission:home_sliders.create')
            ->name('create');

        Route::post('/', [HomeSliderController::class, 'store'])
            ->middleware('permission:home_sliders.create')
            ->name('store');

        Route::get('/{homeSlider}/edit', [HomeSliderController::class, 'edit'])
            ->middleware('permission:home_sliders.update')
            ->name('edit');

        Route::put('/{homeSlider}', [HomeSliderController::class, 'update'])
            ->middleware('permission:home_sliders.update')
            ->name('update');

        Route::patch('/reorder', [HomeSliderController::class, 'reorder'])
            ->middleware('permission:home_sliders.update')
            ->name('reorder');

        Route::patch('/{homeSlider}/toggle-active', [HomeSliderController::class, 'toggleActive'])
            ->middleware('permission:home_sliders.update')
            ->name('toggleActive');

        Route::delete('/{homeSlider}', [HomeSliderController::class, 'destroy'])
            ->middleware('permission:home_sliders.delete')
            ->name('destroy');
    });
});
