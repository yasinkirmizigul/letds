<?php

use App\Http\Controllers\Admin\Gallery\BlogPostGalleryController;
use App\Http\Controllers\Admin\Gallery\GalleryController;
use App\Http\Controllers\Admin\Gallery\GalleryItemsController;
use App\Http\Controllers\Admin\Gallery\ProductGalleryController;
use App\Http\Controllers\Admin\Gallery\ProjectGalleryController;
use App\Http\Controllers\Admin\Media\MediaController;
use Illuminate\Support\Facades\Route;

Route::prefix('media')->as('media.')->group(function () {
    Route::get('/', [MediaController::class, 'index'])
        ->middleware('permission:media.view')
        ->name('index');

    Route::get('/trash', [MediaController::class, 'trash'])
        ->middleware('permission:media.trash')
        ->name('trash');

    Route::get('/list', [MediaController::class, 'list'])
        ->middleware('permission:media.view')
        ->name('list');

    Route::post('/upload', [MediaController::class, 'upload'])
        ->middleware('permission:media.create')
        ->name('upload');

    Route::delete('/bulk-delete', [MediaController::class, 'bulkDestroy'])
        ->middleware('permission:media.delete')
        ->name('bulkDestroy');

    Route::post('/bulk-restore', [MediaController::class, 'bulkRestore'])
        ->middleware('permission:media.restore')
        ->name('bulkRestore');

    Route::delete('/bulk-force-delete', [MediaController::class, 'bulkForceDestroy'])
        ->middleware('permission:media.force_delete')
        ->name('bulkForceDestroy');

    Route::post('/{id}/restore', [MediaController::class, 'restore'])
        ->whereNumber('id')
        ->middleware('permission:media.restore')
        ->name('restore');

    Route::delete('/{id}/force', [MediaController::class, 'forceDestroy'])
        ->whereNumber('id')
        ->middleware('permission:media.force_delete')
        ->name('forceDestroy');

    Route::patch('/{media}', [MediaController::class, 'update'])
        ->whereNumber('media')
        ->middleware('permission:media.update')
        ->name('update');

    Route::delete('/{media}', [MediaController::class, 'destroy'])
        ->whereNumber('media')
        ->middleware('permission:media.delete')
        ->name('destroy');
});

Route::prefix('galleries')->as('galleries.')->group(function () {
    Route::get('/', [GalleryController::class, 'index'])
        ->middleware('permission:galleries.view')
        ->name('index');

    Route::get('/list', [GalleryController::class, 'list'])
        ->middleware('permission:galleries.view')
        ->name('list');

    Route::get('/create', [GalleryController::class, 'create'])
        ->middleware('permission:galleries.create')
        ->name('create');

    Route::post('/', [GalleryController::class, 'store'])
        ->middleware('permission:galleries.create')
        ->name('store');

    Route::get('/{gallery}/edit', [GalleryController::class, 'edit'])
        ->middleware('permission:galleries.update')
        ->name('edit');

    Route::put('/{gallery}', [GalleryController::class, 'update'])
        ->middleware('permission:galleries.update')
        ->name('update');

    Route::delete('/{gallery}', [GalleryController::class, 'destroy'])
        ->middleware('permission:galleries.delete')
        ->name('destroy');

    Route::get('/trash', function () {
        return redirect()->route('admin.trash.index', ['type' => 'gallery']);
    })
        ->middleware('permission:galleries.trash')
        ->name('trash');
});

Route::prefix('galleries/{gallery}/items')->as('galleries.items.')->group(function () {
    Route::get('/', [GalleryItemsController::class, 'items'])
        ->middleware('permission:galleries.update')
        ->name('items');

    Route::post('/', [GalleryItemsController::class, 'store'])
        ->middleware('permission:galleries.update')
        ->name('store');

    Route::patch('/{item}', [GalleryItemsController::class, 'update'])
        ->middleware('permission:galleries.update')
        ->name('update');

    Route::patch('/bulk', [GalleryItemsController::class, 'bulkUpdate'])
        ->middleware('permission:galleries.update')
        ->name('bulk');

    Route::post('/reorder', [GalleryItemsController::class, 'reorder'])
        ->middleware('permission:galleries.update')
        ->name('reorder');

    Route::delete('/{item}', [GalleryItemsController::class, 'destroy'])
        ->middleware('permission:galleries.update')
        ->name('destroy');
});

Route::prefix('blog/{blogPost}/galleries')->as('blog.galleries.')->group(function () {
    Route::get('/', [BlogPostGalleryController::class, 'index'])
        ->middleware('permission:blog.update')
        ->name('index');

    Route::post('/attach', [BlogPostGalleryController::class, 'attach'])
        ->middleware('permission:blog.update')
        ->name('attach');

    Route::post('/detach', [BlogPostGalleryController::class, 'detach'])
        ->middleware('permission:blog.update')
        ->name('detach');

    Route::post('/reorder', [BlogPostGalleryController::class, 'reorder'])
        ->middleware('permission:blog.update')
        ->name('reorder');
});

Route::prefix('projects/{project}/galleries')->as('projects.galleries.')->group(function () {
    Route::get('/', [ProjectGalleryController::class, 'index'])
        ->middleware('permission:projects.update')
        ->name('index');

    Route::post('/attach', [ProjectGalleryController::class, 'attach'])
        ->middleware('permission:projects.update')
        ->name('attach');

    Route::post('/detach', [ProjectGalleryController::class, 'detach'])
        ->middleware('permission:projects.update')
        ->name('detach');

    Route::post('/reorder', [ProjectGalleryController::class, 'reorder'])
        ->middleware('permission:projects.update')
        ->name('reorder');
});

Route::prefix('products/{product}/galleries')->as('products.galleries.')->group(function () {
    Route::get('/', [ProductGalleryController::class, 'index'])
        ->middleware('permission:products.update')
        ->name('index');

    Route::post('/attach', [ProductGalleryController::class, 'attach'])
        ->middleware('permission:products.update')
        ->name('attach');

    Route::post('/detach', [ProductGalleryController::class, 'detach'])
        ->middleware('permission:products.update')
        ->name('detach');

    Route::post('/reorder', [ProductGalleryController::class, 'reorder'])
        ->middleware('permission:products.update')
        ->name('reorder');
});
