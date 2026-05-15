<?php

use App\Http\Controllers\Admin\Profile\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('profile')
    ->as('profile.')
    ->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('index');
        Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
        Route::put('/', [ProfileController::class, 'update'])->name('update');

        Route::post('/avatar', [ProfileController::class, 'updateAvatar'])
            ->name('avatar');

        Route::delete('/avatar', [ProfileController::class, 'removeAvatar'])
            ->name('avatar.remove');
    });
