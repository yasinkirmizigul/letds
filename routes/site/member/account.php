<?php

use App\Http\Controllers\Site\Member\MemberAccountController;
use Illuminate\Support\Facades\Route;

Route::middleware(['site.locale', 'auth:member', 'member.active'])
    ->prefix('member/account')
    ->name('member.account.')
    ->group(function () {
        Route::get('/', [MemberAccountController::class, 'show'])->name('show');
        Route::get('/edit', [MemberAccountController::class, 'edit'])->name('edit');
        Route::put('/', [MemberAccountController::class, 'update'])
            ->middleware('throttle:12,10')
            ->name('update');
        Route::post('/terminate', [MemberAccountController::class, 'terminate'])
            ->middleware('throttle:4,10')
            ->name('terminate');
    });
