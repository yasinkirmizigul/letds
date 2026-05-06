<?php

use App\Http\Controllers\Admin\Ecommerce\CouponController;
use App\Http\Controllers\Admin\Ecommerce\InventoryController;
use App\Http\Controllers\Admin\Ecommerce\InvoiceController;
use App\Http\Controllers\Admin\Ecommerce\OrderController;
use App\Http\Controllers\Admin\Ecommerce\PaymentWebhookEventController;
use App\Http\Controllers\Admin\Ecommerce\ProductVariantController;
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

    Route::prefix('inventory')->as('inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])
            ->middleware('permission:ecommerce_inventory.view')
            ->name('index');

        Route::post('/movements', [InventoryController::class, 'storeMovement'])
            ->middleware('permission:ecommerce_inventory.update')
            ->name('movements.store');

        Route::post('/products/{product}/variants', [ProductVariantController::class, 'store'])
            ->middleware('permission:ecommerce_inventory.update')
            ->name('variants.store');

        Route::put('/variants/{variant}', [ProductVariantController::class, 'update'])
            ->middleware('permission:ecommerce_inventory.update')
            ->name('variants.update');

        Route::delete('/variants/{variant}', [ProductVariantController::class, 'destroy'])
            ->middleware('permission:ecommerce_inventory.update')
            ->name('variants.destroy');
    });

    Route::prefix('coupons')->as('coupons.')->group(function () {
        Route::get('/', [CouponController::class, 'index'])
            ->middleware('permission:ecommerce_coupons.view')
            ->name('index');

        Route::get('/create', [CouponController::class, 'create'])
            ->middleware('permission:ecommerce_coupons.create')
            ->name('create');

        Route::post('/', [CouponController::class, 'store'])
            ->middleware('permission:ecommerce_coupons.create')
            ->name('store');

        Route::get('/{coupon}/edit', [CouponController::class, 'edit'])
            ->middleware('permission:ecommerce_coupons.update')
            ->name('edit');

        Route::put('/{coupon}', [CouponController::class, 'update'])
            ->middleware('permission:ecommerce_coupons.update')
            ->name('update');

        Route::patch('/{coupon}/toggle', [CouponController::class, 'toggle'])
            ->middleware('permission:ecommerce_coupons.update')
            ->name('toggle');

        Route::delete('/{coupon}', [CouponController::class, 'destroy'])
            ->middleware('permission:ecommerce_coupons.delete')
            ->name('destroy');
    });

    Route::prefix('invoices')->as('invoices.')->group(function () {
        Route::get('/', [InvoiceController::class, 'index'])
            ->middleware('permission:ecommerce_invoices.view')
            ->name('index');

        Route::post('/', [InvoiceController::class, 'store'])
            ->middleware('permission:ecommerce_invoices.create')
            ->name('store');

        Route::patch('/{invoice}/status', [InvoiceController::class, 'updateStatus'])
            ->middleware('permission:ecommerce_invoices.update')
            ->name('status');
    });

    Route::prefix('webhooks')->as('webhooks.')->group(function () {
        Route::get('/', [PaymentWebhookEventController::class, 'index'])
            ->middleware('permission:ecommerce_webhooks.view')
            ->name('index');

        Route::patch('/{event}/status', [PaymentWebhookEventController::class, 'updateStatus'])
            ->middleware('permission:ecommerce_webhooks.update')
            ->name('status');
    });
});
