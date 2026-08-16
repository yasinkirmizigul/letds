<?php

use App\Http\Controllers\Admin\AuditLog\AuditLogController;
use App\Http\Controllers\Admin\Menu\AdminMenuVisibilityController;
use App\Http\Controllers\Admin\System\DemoDataFactoryController;
use App\Http\Controllers\Admin\TrashController;
use Illuminate\Support\Facades\Route;

Route::middleware('superadmin')
    ->prefix('menu-visibility')
    ->as('menu-visibility.')
    ->group(function () {
        Route::get('/', [AdminMenuVisibilityController::class, 'edit'])->name('edit');
        Route::put('/', [AdminMenuVisibilityController::class, 'update'])->name('update');
        Route::delete('/', [AdminMenuVisibilityController::class, 'reset'])->name('reset');
    });

Route::middleware('superadmin')
    ->prefix('demo-data')
    ->as('demo-data.')
    ->group(function () {
        Route::get('/', [DemoDataFactoryController::class, 'index'])->name('index');
        Route::post('/generate', [DemoDataFactoryController::class, 'generate'])->name('generate');
        Route::delete('/reset', [DemoDataFactoryController::class, 'reset'])->name('reset');
    });

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
        ->middleware('permission:trash.force_delete')
        ->name('bulkForceDestroy');

    Route::post('/{type}/{id}/restore', [TrashController::class, 'restore'])
        ->middleware('permission:trash.restore')
        ->name('restoreOne');

    Route::delete('/{type}/{id}', [TrashController::class, 'forceDestroy'])
        ->middleware('permission:trash.force_delete')
        ->name('forceDestroyOne');
});

Route::prefix('audit-logs')->as('audit-logs.')->group(function () {
    Route::get('/', [AuditLogController::class, 'index'])
        ->middleware('permission:audit-logs.view')
        ->name('index');

    Route::delete('/', [AuditLogController::class, 'clear'])
        ->middleware('permission:audit-logs.clear')
        ->name('clear');

    Route::get('/{auditLog}', [AuditLogController::class, 'show'])
        ->middleware('permission:audit-logs.view')
        ->name('show');
});
