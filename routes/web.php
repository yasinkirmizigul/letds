<?php

use App\Http\Controllers\Admin\Auth\AuthController;
use App\Http\Controllers\Admin\Gallery\BlogPostGalleryController;
use App\Http\Controllers\Admin\Gallery\GalleryController;
use App\Http\Controllers\Admin\Gallery\GalleryItemsController;
use App\Http\Controllers\Admin\Dash\DashController;
use App\Http\Controllers\Admin\TinyMceController;
use App\Http\Controllers\Admin\User\RoleController;
use App\Http\Controllers\Admin\User\PermissionController;
use App\Http\Controllers\Admin\User\UserController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\BlogPost\BlogPostController;
use App\Http\Controllers\Admin\Project\ProjectController;
use App\Http\Controllers\Admin\Gallery\ProjectGalleryController;
use App\Http\Controllers\Admin\Media\MediaController;
use App\Http\Controllers\Admin\Profile\ProfileController;
use App\Http\Controllers\Admin\TrashController;
use App\Http\Controllers\Admin\AuditLog\AuditLogController;
use App\Http\Controllers\Site\ProjectPublicController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/

Route::get('/login', [AuthController::class, 'showLogin'])
    ->name('login');

Route::post('/login', [AuthController::class, 'login'])
    ->name('login.post');

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
                ->middleware('permission:categories.view')
                ->name('index');

            // ✅ DataTables standard endpoint
            Route::get('/list', [CategoryController::class, 'list'])
                ->middleware('permission:categories.view')
                ->name('list');

            // 🧱 Legacy (eski custom kart listeyi kullanmak istersen)
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
                ->middleware('permission:categories.view')
                ->name('trash');

            Route::get('/trash/list', [CategoryController::class, 'trashList'])
                ->middleware('permission:categories.view')
                ->name('trash.list');

            Route::delete('/{category}', [CategoryController::class, 'destroy'])
                ->middleware('permission:categories.delete')
                ->name('destroy');
        });


        /// Blog
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
                ->middleware(['permission:blog.view']) // en az view
                ->name('checkSlug');
        });


        // =========================
        // Projects (clean section)
        // =========================
        Route::prefix('projects')->as('projects.')->group(function () {

            // pages
            Route::get('/', [ProjectController::class, 'index'])
                ->middleware('permission:projects.view')
                ->name('index');

            Route::get('/trash', [ProjectController::class, 'trash'])
                ->middleware('permission:projects.trash')
                ->name('trash');

            // datatable/json list
            Route::get('/list', [ProjectController::class, 'list'])
                ->middleware('permission:projects.view')
                ->name('list');

            // create/store
            Route::get('/create', [ProjectController::class, 'create'])
                ->middleware('permission:projects.create')
                ->name('create');

            Route::post('/', [ProjectController::class, 'store'])
                ->middleware('permission:projects.create')
                ->name('store');

            // edit/update
            Route::get('/{project}/edit', [ProjectController::class, 'edit'])
                ->middleware('permission:projects.update')
                ->name('edit');

            Route::put('/{project}', [ProjectController::class, 'update'])
                ->middleware('permission:projects.update')
                ->name('update');

            // delete (soft)
            Route::delete('/{project}', [ProjectController::class, 'destroy'])
                ->middleware('permission:projects.delete')
                ->name('destroy');

            // restore / force delete
            Route::post('/{project}/restore', [ProjectController::class, 'restore'])
                ->middleware('permission:projects.restore')
                ->name('restore');

            Route::delete('/{project}/force', [ProjectController::class, 'forceDestroy'])
                ->middleware('permission:projects.force_delete')
                ->name('forceDestroy');

            // bulk actions
            Route::post('/bulk-destroy', [ProjectController::class, 'bulkDestroy'])
                ->middleware('permission:projects.delete')
                ->name('bulkDestroy');

            Route::post('/bulk-restore', [ProjectController::class, 'bulkRestore'])
                ->middleware('permission:projects.restore')
                ->name('bulkRestore');

            Route::post('/bulk-force-destroy', [ProjectController::class, 'bulkForceDestroy'])
                ->middleware('permission:projects.force_delete')
                ->name('bulkForceDestroy');

            // helpers
            Route::get('/check-slug', [ProjectController::class, 'checkSlug'])
                ->middleware('permission:projects.view')
                ->name('checkSlug');

            // workflow status (dropdown)
            Route::patch('/{project}/status', [ProjectController::class, 'updateStatus'])
                ->middleware('permission:projects.update') // istersen: projects.state_change
                ->name('status');

            // featured toggle (max 5 enforced in backend)
            Route::patch('/{project}/featured', [ProjectController::class, 'toggleFeatured'])
                ->middleware('permission:projects.update')
                ->name('featured');
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

            // ✅ bulk route’lar önce (/{media} yutmasın)
            Route::delete('/bulk-delete', [MediaController::class, 'bulkDestroy'])
                ->middleware('permission:media.delete')
                ->name('bulkDestroy');

            Route::post('/bulk-restore', [MediaController::class, 'bulkRestore'])
                ->middleware('permission:media.restore') // ayrı permission istiyorsan
                ->name('bulkRestore');

            Route::delete('/bulk-force-delete', [MediaController::class, 'bulkForceDestroy'])
                ->middleware('permission:media.forceDelete') // ayrı permission istiyorsan
                ->name('bulkForceDestroy');

            // ✅ en son tekil delete + constraint
            Route::delete('/{media}', [MediaController::class, 'destroy'])
                ->whereNumber('media')
                ->middleware('permission:media.delete')
                ->name('destroy');
        });


        // Galleries
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
                return redirect()->route('admin.trash.index');
            })
                ->middleware('permission:trash.view')
                ->name('trash');
        });

        // Gallery Items
        Route::prefix('galleries/{gallery}/items')->as('galleries.items.')->group(function () {

            // GET /admin/galleries/{gallery}/items  -> items()
            Route::get('/', [GalleryItemsController::class, 'items'])
                ->middleware('permission:galleries.update')
                ->name('items');

            // POST /admin/galleries/{gallery}/items -> store()
            Route::post('/', [GalleryItemsController::class, 'store'])
                ->middleware('permission:galleries.update')
                ->name('store');

            // PATCH /admin/galleries/{gallery}/items/{item} -> update()
            Route::patch('/{item}', [GalleryItemsController::class, 'update'])
                ->middleware('permission:galleries.update')
                ->name('update');

            // PATCH /admin/galleries/{gallery}/items/bulk -> bulkUpdate()
            Route::patch('/bulk', [GalleryItemsController::class, 'bulkUpdate'])
                ->middleware('permission:galleries.update')
                ->name('bulk');

            // POST /admin/galleries/{gallery}/items/reorder -> reorder()
            Route::post('/reorder', [GalleryItemsController::class, 'reorder'])
                ->middleware('permission:galleries.update')
                ->name('reorder');

            // DELETE /admin/galleries/{gallery}/items/{item} -> destroy()
            Route::delete('/{item}', [GalleryItemsController::class, 'destroy'])
                ->middleware('permission:galleries.update')
                ->name('destroy');
        });

        // Trash
        Route::prefix('trash')->as('trash.')->group(function () {
            Route::get('/', [TrashController::class, 'index'])
                ->middleware('permission:trash.view')
                ->name('index');

            Route::get('/list', [TrashController::class, 'list'])
                ->middleware('permission:trash.view')
                ->name('list');

            Route::post('/bulk-restore', [TrashController::class, 'bulkRestore'])
                ->middleware('permission:trash.restore')
                ->name('bulkRestore');

            Route::post('/bulk-force-delete', [TrashController::class, 'bulkForceDestroy'])
                ->middleware('permission:trash.force-delete')
                ->name('bulkForceDestroy');

            Route::post('/{type}/{id}/restore', [TrashController::class, 'restore'])
                ->middleware('permission:trash.restore')
                ->name('restoreOne');

            Route::delete('/{type}/{id}', [TrashController::class, 'forceDestroy'])
                ->middleware('permission:trash.force-delete')
                ->name('forceDestroyOne');

        });


        // Audit Logs
        Route::prefix('audit-logs')->as('audit-logs.')->group(function () {
            Route::get('/', [AuditLogController::class, 'index'])
                ->middleware('permission:audit-logs.view')
                ->name('index');

            Route::get('/{auditLog}', [AuditLogController::class, 'show'])
                ->middleware('permission:audit-logs.view')
                ->name('show');
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

        // Projects ↔ Galleries
        Route::prefix('projects/{project}/galleries')->as('projects.galleries.')->group(function () {
            Route::get('/', [ProjectGalleryController::class, 'index'])
                ->middleware('permission:projects.update')
                ->name('index');

            // Bulk actions
            Route::post('/projects/bulk-delete', [ProjectController::class, 'bulkDelete'])
                ->name('projects.bulkDelete');

            Route::post('/projects/bulk-restore', [ProjectController::class, 'bulkRestore'])
                ->name('projects.bulkRestore');

            Route::post('/projects/bulk-force-delete', [ProjectController::class, 'bulkForceDelete'])
                ->name('projects.bulkForceDelete');

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

        Route::post('/tinymce/upload', [TinyMceController::class, 'upload'])
            ->name('tinymce.upload');
        /*        Route::get('media/trash', fn() => redirect()->route('admin.media.index', ['mode' => 'trash']))
                    ->name('media.trash');
                Route::get('galleries/trash', fn() => redirect()->route('admin.galleries.index', ['mode' => 'trash']))
                    ->name('galleries.trash');*/
    });

// [ADMIN_MODULE_ROUTES]
require __DIR__ . '/admin/modules/test_products.php';

// Public Projects
Route::get('/projects/{slug}', [ProjectPublicController::class, 'show'])
    ->name('projects.show');
