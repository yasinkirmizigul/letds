<?php

use App\Http\Controllers\Admin\BlogPost\BlogPostController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\Project\ProjectController;
use Illuminate\Support\Facades\Route;

Route::prefix('categories')->as('categories.')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])
        ->middleware('permission:categories.view')
        ->name('index');

    Route::get('/list', [CategoryController::class, 'list'])
        ->middleware('permission:categories.view')
        ->name('list');

    Route::get('/list-legacy', [CategoryController::class, 'listLegacy'])
        ->middleware('permission:categories.view')
        ->name('list_legacy');

    Route::get('/create', [CategoryController::class, 'create'])
        ->middleware('permission:categories.create')
        ->name('create');

    Route::post('/', [CategoryController::class, 'store'])
        ->middleware('permission:categories.create')
        ->name('store');

    Route::get('/{category}/edit', [CategoryController::class, 'edit'])
        ->middleware('permission:categories.update')
        ->name('edit');

    Route::put('/{category}', [CategoryController::class, 'update'])
        ->middleware('permission:categories.update')
        ->name('update');

    Route::get('/trash', [CategoryController::class, 'trash'])
        ->middleware('permission:categories.trash')
        ->name('trash');

    Route::get('/trash/list', [CategoryController::class, 'trashList'])
        ->middleware('permission:categories.view')
        ->name('trash.list');

    Route::delete('/{category}', [CategoryController::class, 'destroy'])
        ->middleware('permission:categories.delete')
        ->name('destroy');

    Route::post('/{id}/restore', [CategoryController::class, 'restore'])
        ->middleware('permission:categories.restore')
        ->name('restore');

    Route::delete('/{id}/force', [CategoryController::class, 'forceDestroy'])
        ->middleware('permission:categories.force_delete')
        ->name('forceDestroy');

    Route::post('/bulk-delete', [CategoryController::class, 'bulkDestroy'])
        ->middleware('permission:categories.delete')
        ->name('bulkDestroy');

    Route::post('/bulk-restore', [CategoryController::class, 'bulkRestore'])
        ->middleware('permission:categories.restore')
        ->name('bulkRestore');

    Route::post('/bulk-force-delete', [CategoryController::class, 'bulkForceDestroy'])
        ->middleware('permission:categories.force_delete')
        ->name('bulkForceDestroy');
});

Route::prefix('blog')->as('blog.')->group(function () {
    Route::get('/', [BlogPostController::class, 'index'])
        ->middleware('permission:blog.view')
        ->name('index');

    Route::get('/trash', [BlogPostController::class, 'trash'])
        ->middleware('permission:blog.trash')
        ->name('trash');

    Route::get('/list', [BlogPostController::class, 'list'])
        ->middleware('permission:blog.view')
        ->name('list');

    Route::get('/create', [BlogPostController::class, 'create'])
        ->middleware('permission:blog.create')
        ->name('create');

    Route::post('/', [BlogPostController::class, 'store'])
        ->middleware('permission:blog.create')
        ->name('store');

    Route::get('/{blogPost}/edit', [BlogPostController::class, 'edit'])
        ->middleware('permission:blog.update')
        ->name('edit');

    Route::put('/{blogPost}', [BlogPostController::class, 'update'])
        ->middleware('permission:blog.update')
        ->name('update');

    Route::delete('/{blogPost}', [BlogPostController::class, 'destroy'])
        ->middleware('permission:blog.delete')
        ->name('destroy');

    Route::post('/{id}/restore', [BlogPostController::class, 'restore'])
        ->middleware('permission:blog.restore')
        ->name('restore');

    Route::delete('/{id}/force', [BlogPostController::class, 'forceDestroy'])
        ->middleware('permission:blog.force_delete')
        ->name('forceDestroy');

    Route::post('/bulk-delete', [BlogPostController::class, 'bulkDestroy'])
        ->middleware('permission:blog.delete')
        ->name('bulkDestroy');

    Route::post('/bulk-restore', [BlogPostController::class, 'bulkRestore'])
        ->middleware('permission:blog.restore')
        ->name('bulkRestore');

    Route::post('/bulk-force-delete', [BlogPostController::class, 'bulkForceDestroy'])
        ->middleware('permission:blog.force_delete')
        ->name('bulkForceDestroy');

    Route::patch('/{blogPost}/toggle-publish', [BlogPostController::class, 'togglePublish'])
        ->middleware('permission:blog.update')
        ->name('togglePublish');

    Route::patch('/{blogPost}/toggle-featured', [BlogPostController::class, 'toggleFeatured'])
        ->middleware('permission:blog.update')
        ->name('toggleFeatured');

    Route::get('/check-slug', [BlogPostController::class, 'checkSlug'])
        ->middleware(['permission:blog.view'])
        ->name('checkSlug');
});

Route::prefix('projects')->as('projects.')->group(function () {
    Route::get('/', [ProjectController::class, 'index'])
        ->middleware('permission:projects.view')
        ->name('index');

    Route::get('/trash', [ProjectController::class, 'trash'])
        ->middleware('permission:projects.trash')
        ->name('trash');

    Route::get('/list', [ProjectController::class, 'list'])
        ->middleware('permission:projects.view')
        ->name('list');

    Route::get('/create', [ProjectController::class, 'create'])
        ->middleware('permission:projects.create')
        ->name('create');

    Route::post('/', [ProjectController::class, 'store'])
        ->middleware('permission:projects.create')
        ->name('store');

    Route::get('/{project}/edit', [ProjectController::class, 'edit'])
        ->middleware('permission:projects.update')
        ->name('edit');

    Route::put('/{project}', [ProjectController::class, 'update'])
        ->middleware('permission:projects.update')
        ->name('update');

    Route::delete('/{project}', [ProjectController::class, 'destroy'])
        ->middleware('permission:projects.delete')
        ->name('destroy');

    Route::post('/{project}/restore', [ProjectController::class, 'restore'])
        ->middleware('permission:projects.restore')
        ->name('restore');

    Route::delete('/{project}/force', [ProjectController::class, 'forceDestroy'])
        ->middleware('permission:projects.force_delete')
        ->name('forceDestroy');

    Route::post('/bulk-destroy', [ProjectController::class, 'bulkDestroy'])
        ->middleware('permission:projects.delete')
        ->name('bulkDestroy');

    Route::post('/bulk-restore', [ProjectController::class, 'bulkRestore'])
        ->middleware('permission:projects.restore')
        ->name('bulkRestore');

    Route::post('/bulk-force-destroy', [ProjectController::class, 'bulkForceDestroy'])
        ->middleware('permission:projects.force_delete')
        ->name('bulkForceDestroy');

    Route::get('/check-slug', [ProjectController::class, 'checkSlug'])
        ->middleware('permission:projects.view')
        ->name('checkSlug');

    Route::patch('/{project}/status', [ProjectController::class, 'updateStatus'])
        ->middleware('permission:projects.update')
        ->name('status');

    Route::patch('/{project}/featured', [ProjectController::class, 'toggleFeatured'])
        ->middleware('permission:projects.update')
        ->name('featured');
});
