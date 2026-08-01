<?php

use App\Http\Controllers\Site\Member\ServiceReviewController;
use Illuminate\Support\Facades\Route;

Route::middleware(['site.locale', 'auth:member', 'member.active'])
    ->prefix('member/reviews')
    ->name('member.reviews.')
    ->group(function () {
        Route::get('/', [ServiceReviewController::class, 'index'])->name('index');
        Route::get('/{serviceReview}', [ServiceReviewController::class, 'show'])
            ->whereNumber('serviceReview')
            ->name('show');
        Route::post('/{serviceReview}', [ServiceReviewController::class, 'store'])
            ->whereNumber('serviceReview')
            ->middleware('throttle:10,1')
            ->name('store');
    });
