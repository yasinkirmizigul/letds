<?php

use App\Http\Controllers\Admin\Notification\AdminNotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('notifications')->as('notifications.')->group(function () {
    Route::get('/', [AdminNotificationController::class, 'index'])
        ->middleware('permission:notifications.view')
        ->name('index');

    Route::post('/read-all', [AdminNotificationController::class, 'readAll'])
        ->middleware('permission:notifications.update')
        ->name('readAll');

    Route::patch('/{notification}/read', [AdminNotificationController::class, 'read'])
        ->middleware('permission:notifications.update')
        ->name('read');

    Route::patch('/{notification}/dismiss', [AdminNotificationController::class, 'dismiss'])
        ->middleware('permission:notifications.update')
        ->name('dismiss');
});
