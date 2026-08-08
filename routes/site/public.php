<?php

use App\Http\Controllers\Site\Blog\BlogController;
use App\Http\Controllers\Site\Gallery\GalleryController;
use Illuminate\Support\Facades\Route;

$registerPublicContentRoutes = static function (): void {
    Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
    Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

    Route::get('/galeri', [GalleryController::class, 'index'])->name('galleries.index');
    Route::get('/galeri/{slug}', [GalleryController::class, 'show'])->name('galleries.show');
};

Route::middleware('site.locale')->name('site.')->group($registerPublicContentRoutes);

Route::middleware('site.locale')
    ->prefix('{locale}')
    ->where(['locale' => '[A-Za-z]{2}(?:-[A-Za-z]{2})?'])
    ->name('site.')
    ->group(function () use ($registerPublicContentRoutes): void {
        Route::name('localized.')->group($registerPublicContentRoutes);
    });
