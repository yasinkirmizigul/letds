<?php

use App\Http\Controllers\Admin\Auth\AuthController;
use App\Http\Controllers\Admin\BlogPost\BlogPostController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\Dash\DashController;
use App\Http\Controllers\Admin\Media\MediaController;
use App\Http\Controllers\Admin\TinyMceController;
use App\Http\Controllers\Admin\User\PermissionController;
use App\Http\Controllers\Admin\User\RoleController;
use App\Http\Controllers\Admin\User\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


Route::middleware(['auth'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {
        Route::get('/', [DashController::class, 'index'])->name('dashboard');

        // Roles (admin + superadmin)
        Route::middleware('permission:roles.view')->group(function () {
            Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
            Route::get('/roles/create', [RoleController::class, 'create'])->middleware('permission:roles.create')->name('roles.create');
            Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:roles.create')->name('roles.store');
            Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->middleware('permission:roles.update')->name('roles.edit');
            Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('permission:roles.update')->name('roles.update');
            Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
                ->name('admin.roles.destroy')
                ->middleware('permission:roles.delete');
        });

        // Permissions (admin + superadmin)
        Route::middleware('permission:permissions.view')->group(function () {
            Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
            Route::get('/permissions/create', [PermissionController::class, 'create'])->middleware('permission:permissions.create')->name('permissions.create');
            Route::post('/permissions', [PermissionController::class, 'store'])->middleware('permission:permissions.create')->name('permissions.store');
            Route::get('/permissions/{permission}/edit', [PermissionController::class, 'edit'])->middleware('permission:permissions.update')->name('permissions.edit');
            Route::put('/permissions/{permission}', [PermissionController::class, 'update'])->middleware('permission:permissions.update')->name('permissions.update');
            Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])->middleware('permission:permissions.delete')->name('permissions.destroy');
        });

        // Users (admin erişemez çünkü users.* permission'ları yok)
        Route::middleware('permission:users.view')->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::get('/users/create', [UserController::class, 'create'])->middleware('permission:users.create')->name('users.create');
            Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])->middleware('permission:users.update')->name('users.edit');
            Route::put('/users/{user}', [UserController::class, 'update'])->middleware('permission:users.update')->name('users.update');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete')->name('users.destroy');
        });
    });
Route::middleware(['auth'])
    ->prefix('admin/categories')
    ->as('admin.categories.')
    ->group(function () {

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

        // ✅ canlı slug kontrol
        Route::get('/check-slug', [CategoryController::class, 'checkSlug'])
            ->middleware('permission:category.view')
            ->name('checkSlug');
    });

Route::middleware(['auth'])
    ->prefix('admin/blog')
    ->as('admin.blog.')
    ->group(function () {

        // 📄 Liste (view)
        Route::middleware('permission:blog.view')->group(function () {
            Route::get('/', [BlogPostController::class, 'index'])->name('index');
        });

        // ✍️ Create
        Route::middleware('permission:blog.create')->group(function () {
            Route::get('/create', [BlogPostController::class, 'create'])->name('create');
            Route::post('/', [BlogPostController::class, 'store'])->name('store');
        });

        // ✏️ Update
        Route::middleware('permission:blog.update')->group(function () {
            Route::get('/{blogPost}/edit', [BlogPostController::class, 'edit'])->name('edit');
            Route::put('/{blogPost}', [BlogPostController::class, 'update'])->name('update');
        });

        // 🗑 Delete
        Route::middleware('permission:blog.delete')->group(function () {
            Route::delete('/{blogPost}', [BlogPostController::class, 'destroy'])->name('destroy');
        });

        Route::patch('/{blogPost}/toggle-publish', [BlogPostController::class, 'togglePublish'])
            ->middleware('permission:blog.update')
            ->name('togglePublish');
    });

Route::middleware(['auth'])
    ->prefix('admin/media')
    ->as('admin.media.')
    ->group(function () {
        Route::get('/', [MediaController::class, 'index'])
            ->middleware('permission:media.view')
            ->name('index');

        Route::get('/list', [MediaController::class, 'list'])
            ->middleware('permission:media.view')
            ->name('list');

        Route::post('/upload', [MediaController::class, 'upload'])
            ->middleware('permission:media.upload')
            ->name('upload');

        Route::delete('/{media}', [MediaController::class, 'destroy'])
            ->middleware('permission:media.delete')
            ->name('destroy');
    });
Route::middleware(['auth'])
    ->prefix('admin/profile')
    ->as('admin.profile.')
    ->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])
            ->name('edit');

        Route::post('/avatar', [ProfileController::class, 'updateAvatar'])
            ->name('avatar')
            ->middleware('permission:users.update'); // istersen ayrı permission: profile.update
    });

Route::middleware(['auth'])->prefix('admin')->as('admin.')->group(function () {
    Route::post('/tinymce/upload', [TinyMceController::class, 'upload'])->name('tinymce.upload');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');
