<?php

use App\Http\Controllers\Admin\Review\ServiceReviewController;
use App\Http\Controllers\Admin\Review\ServiceReviewQuestionController;
use Illuminate\Support\Facades\Route;

Route::prefix('service-reviews')->as('service-reviews.')->group(function () {
    Route::get('/', [ServiceReviewController::class, 'index'])
        ->middleware('permission:service_reviews.view')
        ->name('index');

    Route::post('/sync', [ServiceReviewController::class, 'sync'])
        ->middleware('permission:service_reviews.questions')
        ->name('sync');

    Route::prefix('questions')->as('questions.')->group(function () {
        Route::get('/', [ServiceReviewQuestionController::class, 'index'])
            ->middleware('permission:service_reviews.questions')
            ->name('index');

        Route::post('/', [ServiceReviewQuestionController::class, 'store'])
            ->middleware('permission:service_reviews.questions')
            ->name('store');

        Route::put('/{question}', [ServiceReviewQuestionController::class, 'update'])
            ->whereNumber('question')
            ->middleware('permission:service_reviews.questions')
            ->name('update');

        Route::delete('/{question}', [ServiceReviewQuestionController::class, 'destroy'])
            ->whereNumber('question')
            ->middleware('permission:service_reviews.questions')
            ->name('destroy');
    });

    Route::get('/{serviceReview}', [ServiceReviewController::class, 'show'])
        ->whereNumber('serviceReview')
        ->middleware('permission:service_reviews.view')
        ->name('show');
});
