<?php

use App\Http\Controllers\Admin\Ecommerce\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('ecommerce')->as('ecommerce.')->group(function () {
    Route::prefix('orders')->as('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])
            ->middleware('permission:ecommerce_orders.view')
            ->name('index');

        Route::get('/create', [OrderController::class, 'create'])
            ->middleware('permission:ecommerce_orders.create')
            ->name('create');

        Route::post('/', [OrderController::class, 'store'])
            ->middleware('permission:ecommerce_orders.create')
            ->name('store');

        Route::get('/{order}', [OrderController::class, 'show'])
            ->middleware('permission:ecommerce_orders.view')
            ->name('show');

        Route::get('/{order}/edit', [OrderController::class, 'edit'])
            ->middleware('permission:ecommerce_orders.update')
            ->name('edit');

        Route::put('/{order}', [OrderController::class, 'update'])
            ->middleware('permission:ecommerce_orders.update')
            ->name('update');

        Route::delete('/{order}', [OrderController::class, 'destroy'])
            ->middleware('permission:ecommerce_orders.delete')
            ->name('destroy');

        Route::post('/{order}/transactions', [OrderController::class, 'storeTransaction'])
            ->middleware('permission:ecommerce_orders.payments')
            ->name('transactions.store');

        Route::post('/{order}/shipments', [OrderController::class, 'storeShipment'])
            ->middleware('permission:ecommerce_orders.shipments')
            ->name('shipments.store');
    });
});
