<?php

use App\Http\Controllers\Admin\Auth\AuthController;
use App\Http\Controllers\Admin\Gallery\BlogPostGalleryController;
use App\Http\Controllers\Admin\Gallery\GalleryController;
use App\Http\Controllers\Admin\Gallery\GalleryItemsController;
use App\Http\Controllers\Admin\Dash\DashController;
use App\Http\Controllers\Admin\User\RoleController;
use App\Http\Controllers\Admin\User\PermissionController;
use App\Http\Controllers\Admin\User\UserController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\BlogPost\BlogPostController;
use App\Http\Controllers\Admin\Media\MediaController;
use App\Http\Controllers\Admin\Profile\ProfileController;
use App\Http\Controllers\Admin\TrashController;
use App\Http\Controllers\Admin\AuditLog\AuditLogController;
use App\Http\Controllers\Admin\TinyMceController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'audit'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {

        Route::get('/', [DashController::class, 'index'])->name('dashboard');

        // Roles
        Route::middleware('permission:roles.view')->group(function () {
            Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');

            Route::get('/roles/create', [RoleController::class, 'create'])
                ->middleware('permission:roles.create')
                ->name('roles.create');

            Route::post('/roles', [RoleController::class, 'store'])
                ->middleware('permission:roles.create')
                ->name('roles.store');

            Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])
                ->middleware('permission:roles.update')
                ->name('roles.edit');

            Route::put('/roles/{role}', [RoleController::class, 'update'])
                ->middleware('permission:roles.update')
                ->name('roles.update');

            Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
                ->middleware('permission:roles.delete')
                ->name('roles.destroy');
        });

        // Permissions
        Route::middleware('permission:permissions.view')->group(function () {
            Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');

            Route::get('/permissions/create', [PermissionController::class, 'create'])
                ->middleware('permission:permissions.create')
                ->name('permissions.create');

            Route::post('/permissions', [PermissionController::class, 'store'])
                ->middleware('permission:permissions.create')
                ->name('permissions.store');

            Route::get('/permissions/{permission}/edit', [PermissionController::class, 'edit'])
                ->middleware('permission:permissions.update')
                ->name('permissions.edit');

            Route::put('/permissions/{permission}', [PermissionController::class, 'update'])
                ->middleware('permission:permissions.update')
                ->name('permissions.update');

            Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])
                ->middleware('permission:permissions.delete')
                ->name('permissions.destroy');
        });

        // Users
        Route::middleware('permission:users.view')->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');

            Route::get('/users/create', [UserController::class, 'create'])
                ->middleware('permission:users.create')
                ->name('users.create');

            Route::post('/users', [UserController::class, 'store'])
                ->middleware('permission:users.create')
                ->name('users.store');

            Route::get('/users/{user}/edit', [UserController::class, 'edit'])
                ->middleware('permission:users.update')
                ->name('users.edit');

            Route::put('/users/{user}', [UserController::class, 'update'])
                ->middleware('permission:users.update')
                ->name('users.update');

            Route::delete('/users/{user}', [UserController::class, 'destroy'])
                ->middleware('permission:users.delete')
                ->name('users.destroy');
        });

        // Categories
        Route::prefix('categories')->as('categories.')->group(function () {
            Route::get('/', [CategoryController::class, 'index'])
                ->middleware('permission:category.view')
                ->name('index');

            Route::get('/trash', [CategoryController::class, 'trash'])
                ->middleware('permission:category.trash')
                ->name('trash');

            Route::get('/list', [CategoryController::class, 'list'])
                ->middleware('permission:category.view')
                ->name('list');

            Route::get('/create', [CategoryController::class, 'create'])
                ->middleware('permission:category.create')
                ->name('create');

            Route::post('/', [CategoryController::class, 'store'])
                ->middleware('permission:category.create')
                ->name('store');

            Route::get('/{category}/edit', [CategoryController::class, 'edit'])
                ->middleware('permission:category.update')
                ->name('edit');

            Route::put('/{category}', [CategoryController::class, 'update'])
                ->middleware('permission:category.update')
                ->name('update');

            Route::delete('/{category}', [CategoryController::class, 'destroy'])
                ->middleware('permission:category.delete')
                ->name('destroy');

            Route::post('/{id}/restore', [CategoryController::class, 'restore'])
                ->middleware('permission:category.restore')
                ->name('restore');

            Route::delete('/{id}/force', [CategoryController::class, 'forceDestroy'])
                ->middleware('permission:category.force_delete')
                ->name('forceDestroy');

            Route::post('/bulk-delete', [CategoryController::class, 'bulkDestroy'])
                ->middleware('permission:category.delete')
                ->name('bulkDestroy');

            Route::post('/bulk-restore', [CategoryController::class, 'bulkRestore'])
                ->middleware('permission:category.restore')
                ->name('bulkRestore');

            Route::post('/bulk-force-delete', [CategoryController::class, 'bulkForceDestroy'])
                ->middleware('permission:category.force_delete')
                ->name('bulkForceDestroy');

            Route::get('/check-slug', [CategoryController::class, 'checkSlug'])
                ->middleware('permission:category.view')
                ->name('checkSlug');
        });

        // Blog
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
        });

        // Media
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

            Route::delete('/{media}', [MediaController::class, 'destroy'])
                ->middleware('permission:media.delete')
                ->name('destroy');

            Route::post('/{id}/restore', [MediaController::class, 'restore'])
                ->middleware('permission:media.restore')
                ->name('restore');

            Route::delete('/{id}/force', [MediaController::class, 'forceDestroy'])
                ->middleware('permission:media.force_delete')
                ->name('forceDestroy');

            Route::post('/bulk-delete', [MediaController::class, 'bulkDestroy'])
                ->middleware('permission:media.delete')
                ->name('bulkDestroy');

            Route::post('/bulk-restore', [MediaController::class, 'bulkRestore'])
                ->middleware('permission:media.restore')
                ->name('bulkRestore');

            Route::post('/bulk-force-delete', [MediaController::class, 'bulkForceDestroy'])
                ->middleware('permission:media.force_delete')
                ->name('bulkForceDestroy');
        });

        // Galleries
        Route::prefix('galleries')->as('galleries.')->group(function () {
            Route::get('/', [GalleryController::class, 'index'])
                ->middleware('permission:gallery.view')
                ->name('index');

            Route::get('/trash', [GalleryController::class, 'trash'])
                ->middleware('permission:gallery.trash')
                ->name('trash');

            // JSON endpoints
            Route::get('/list', [GalleryController::class, 'list'])
                ->middleware('permission:gallery.view')
                ->name('list');

            Route::get('/{gallery}/items', [GalleryItemsController::class, 'items'])
                ->middleware('permission:gallery.view')
                ->name('items');

            Route::post('/{gallery}/items', [GalleryItemsController::class, 'store'])
                ->middleware('permission:gallery.update')
                ->name('items.store');

            Route::patch('/{gallery}/items/{item}', [GalleryItemsController::class, 'update'])
                ->middleware('permission:gallery.update')
                ->name('items.update');

            Route::patch('/{gallery}/items/bulk', [GalleryItemsController::class, 'bulkUpdate'])
                ->middleware('permission:gallery.update')
                ->name('items.bulk');

            Route::delete('/{gallery}/items/{item}', [GalleryItemsController::class, 'destroy'])
                ->middleware('permission:gallery.update')
                ->name('items.destroy');

            Route::post('/{gallery}/items/reorder', [GalleryItemsController::class, 'reorder'])
                ->middleware('permission:gallery.update')
                ->name('items.reorder');

            // CRUD
            Route::get('/create', [GalleryController::class, 'create'])
                ->middleware('permission:gallery.create')
                ->name('create');

            Route::post('/', [GalleryController::class, 'store'])
                ->middleware('permission:gallery.create')
                ->name('store');

            Route::get('/{gallery}/edit', [GalleryController::class, 'edit'])
                ->middleware('permission:gallery.update')
                ->name('edit');

            Route::put('/{gallery}', [GalleryController::class, 'update'])
                ->middleware('permission:gallery.update')
                ->name('update');

            Route::delete('/{gallery}', [GalleryController::class, 'destroy'])
                ->middleware('permission:gallery.delete')
                ->name('destroy');

            Route::post('/{id}/restore', [GalleryController::class, 'restore'])
                ->middleware('permission:gallery.restore')
                ->name('restore');

            Route::delete('/{id}/force', [GalleryController::class, 'forceDestroy'])
                ->middleware('permission:gallery.force_delete')
                ->name('forceDestroy');

            Route::post('/bulk-delete', [GalleryController::class, 'bulkDestroy'])
                ->middleware('permission:gallery.delete')
                ->name('bulkDestroy');

            Route::post('/bulk-restore', [GalleryController::class, 'bulkRestore'])
                ->middleware('permission:gallery.restore')
                ->name('bulkRestore');

            Route::post('/bulk-force-delete', [GalleryController::class, 'bulkForceDestroy'])
                ->middleware('permission:gallery.force_delete')
                ->name('bulkForceDestroy');
        });

        // Blog ↔ Galleries
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

        // Profile
        Route::middleware(['auth'])
            ->prefix('profile')
            ->as('profile.')
            ->group(function () {
                Route::get('/', [ProfileController::class, 'index'])->name('index');
                Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
                Route::put('/', [ProfileController::class, 'update'])->name('update');

                Route::post('/avatar', [ProfileController::class, 'updateAvatar'])
                    ->middleware('permission:users.update')
                    ->name('avatar');

                Route::delete('/avatar', [ProfileController::class, 'removeAvatar'])
                    ->middleware('permission:users.update')
                    ->name('avatar.remove');
            });

        Route::prefix('trash')->as('trash.')->group(function () {
            Route::get('/', [TrashController::class, 'index'])
                ->middleware('permission:trash.view')
                ->name('index');

            Route::get('/list', [TrashController::class, 'list'])
                ->middleware('permission:trash.view')
                ->name('list');

            Route::post('/bulk-restore', [TrashController::class, 'bulkRestore'])
                ->middleware('permission:trash.view')
                ->name('bulkRestore');

            Route::post('/bulk-force-delete', [TrashController::class, 'bulkForceDestroy'])
                ->middleware('permission:trash.view')
                ->name('bulkForceDestroy');
        });

        Route::prefix('audit-logs')->as('audit-logs.')->group(function () {
            Route::get('/', [AuditLogController::class, 'index'])
                ->middleware('permission:audit.view')
                ->name('index');

            Route::get('/{auditLog}', [AuditLogController::class, 'show'])
                ->middleware('permission:audit.view')
                ->name('show');
        });

        Route::post('/tinymce/upload', [TinyMceController::class, 'upload'])->name('tinymce.upload');
    });
