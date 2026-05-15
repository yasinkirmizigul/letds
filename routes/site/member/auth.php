<?php

use App\Http\Controllers\Site\Auth\MemberAuthController;
use App\Http\Controllers\Site\Auth\MemberPasswordResetController;
use Illuminate\Support\Facades\Route;

Route::middleware(['site.locale', 'guest:member'])->group(function () {
    Route::get('/uye-kayit', [MemberAuthController::class, 'showRegister'])
        ->name('member.register');

    Route::post('/uye-kayit', [MemberAuthController::class, 'register'])
        ->middleware('throttle:6,10')
        ->name('member.register.post');

    Route::get('/member/forgot-password', [MemberPasswordResetController::class, 'showLinkRequestForm'])
        ->name('member.password.request');

    Route::post('/member/forgot-password', [MemberPasswordResetController::class, 'sendResetLinkEmail'])
        ->middleware('throttle:6,10')
        ->name('member.password.email');

    Route::get('/member/reset-password/{token}', [MemberPasswordResetController::class, 'showResetForm'])
        ->name('member.password.reset');

    Route::post('/member/reset-password', [MemberPasswordResetController::class, 'reset'])
        ->middleware('throttle:6,10')
        ->name('member.password.update');
});

Route::middleware('site.locale')->prefix('member')->name('member.')->group(function () {
    Route::middleware('guest:member')->group(function () {
        Route::get('/login', [MemberAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [MemberAuthController::class, 'login'])
            ->middleware('throttle:10,1')
            ->name('login.post');
    });

    Route::post('/logout', [MemberAuthController::class, 'logout'])
        ->middleware('auth:member')
        ->name('logout');
});

Route::middleware('site.locale')->group(function () {
    Route::get('/uyelik-bilgilendirmesi', [MemberAuthController::class, 'showMembershipInformation'])
        ->name('member.terms.show');
});
