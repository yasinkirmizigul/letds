<?php

use App\Http\Controllers\Admin\Auth\AuthController;
use App\Http\Controllers\Admin\TinyMceController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Dash\DashController;
use App\Http\Controllers\Admin\User\RoleController;
use App\Http\Controllers\Admin\User\PermissionController;
use App\Http\Controllers\Admin\User\UserController;
use App\Http\Controllers\Admin\BlogPost\BlogPostController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\Media\MediaController;
use App\Http\Controllers\Admin\Profile\ProfileController;

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/
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
Route::middleware(['auth'])
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
                ->name('roles.destroy'); // ✅ FIX: admin.roles.destroy olur
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

            Route::get('/check-slug', [CategoryController::class, 'checkSlug'])
                ->middleware('permission:category.view')
                ->name('checkSlug');
        });

        // Blog
        Route::prefix('blog')->as('blog.')->group(function () {
            Route::get('/', [BlogPostController::class, 'index'])
                ->middleware('permission:blog.view')
                ->name('index');

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

            Route::patch('/{blogPost}/toggle-publish', [BlogPostController::class, 'togglePublish'])
                ->middleware('permission:blog.update')
                ->name('togglePublish');
        });

// Media
        Route::prefix('media')->as('media.')->group(function () {
            Route::get('/', [MediaController::class, 'index'])
                ->middleware('permission:media.view')
                ->name('index');

            Route::get('/list', [MediaController::class, 'list'])
                ->middleware('permission:media.view')
                ->name('list');

            Route::post('/upload', [MediaController::class, 'upload'])
                ->middleware('permission:media.upload')
                ->name('upload');

            // ✅ BULK: önce + path doğru
            Route::delete('/bulk', [MediaController::class, 'bulkDestroy'])
                ->middleware('permission:media.delete')
                ->name('bulkDestroy');

            // ✅ tekil silme her zaman en sona
            Route::delete('/{media}', [MediaController::class, 'destroy'])
                ->middleware('permission:media.delete')
                ->name('destroy');
        });

// Profile
        Route::middleware(['auth'])
            ->prefix('profile')
            ->as('profile.')
            ->group(function () {

                // Profil özet sayfası
                Route::get('/', [ProfileController::class, 'index'])
                    ->name('index');

                // Düzenleme formu
                Route::get('/edit', [ProfileController::class, 'edit'])
                    ->name('edit');

                // Profil bilgilerini kaydet
                Route::put('/', [ProfileController::class, 'update'])
                    ->name('update');

                // Avatar yükle
                Route::post('/avatar', [ProfileController::class, 'updateAvatar'])
                    ->middleware('permission:users.update')
                    ->name('avatar');

                // Avatar kaldır
                Route::delete('/avatar', [ProfileController::class, 'removeAvatar'])
                    ->middleware('permission:users.update')
                    ->name('avatar.remove');
            });

        // TinyMCE upload (admin grubu içinde zaten)
        Route::post('/tinymce/upload', [TinyMceController::class, 'upload'])->name('tinymce.upload');
    });
