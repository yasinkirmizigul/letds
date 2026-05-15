<?php

use App\Http\Controllers\Admin\Product\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('products')->as('products.')->group(function () {
    Route::get('/', [ProductController::class, 'index'])
        ->middleware('permission:products.view')
        ->name('index');

    Route::get('/trash', [ProductController::class, 'trash'])
        ->middleware('permission:products.trash')
        ->name('trash');

    Route::get('/list', [ProductController::class, 'list'])
        ->middleware('permission:products.view')
        ->name('list');

    Route::get('/create', [ProductController::class, 'create'])
        ->middleware('permission:products.create')
        ->name('create');

    Route::post('/', [ProductController::class, 'store'])
        ->middleware('permission:products.create')
        ->name('store');

    Route::get('/{product}/edit', [ProductController::class, 'edit'])
        ->middleware('permission:products.update')
        ->name('edit');

    Route::put('/{product}', [ProductController::class, 'update'])
        ->middleware('permission:products.update')
        ->name('update');

    Route::delete('/{product}', [ProductController::class, 'destroy'])
        ->middleware('permission:products.delete')
        ->name('destroy');

    Route::post('/{product}/restore', [ProductController::class, 'restore'])
        ->middleware('permission:products.restore')
        ->name('restore');

    Route::delete('/{product}/force', [ProductController::class, 'forceDestroy'])
        ->middleware('permission:products.force_delete')
        ->name('forceDestroy');

    Route::post('/bulk-destroy', [ProductController::class, 'bulkDestroy'])
        ->middleware('permission:products.delete')
        ->name('bulkDestroy');

    Route::post('/bulk-restore', [ProductController::class, 'bulkRestore'])
        ->middleware('permission:products.restore')
        ->name('bulkRestore');

    Route::post('/bulk-force-destroy', [ProductController::class, 'bulkForceDestroy'])
        ->middleware('permission:products.force_delete')
        ->name('bulkForceDestroy');

    Route::get('/check-slug', [ProductController::class, 'checkSlug'])
        ->middleware('permission:products.view')
        ->name('checkSlug');

    Route::patch('/{product}/status', [ProductController::class, 'updateStatus'])
        ->middleware('permission:products.update')
        ->name('status');

    Route::patch('/{product}/featured', [ProductController::class, 'toggleFeatured'])
        ->middleware('permission:products.update')
        ->name('featured');
});
