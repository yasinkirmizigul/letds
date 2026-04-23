<?php

use App\Http\Controllers\Admin\Member\MemberController;
use Illuminate\Support\Facades\Route;

Route::prefix('members')->as('members.')->group(function () {
    Route::get('/', [MemberController::class, 'index'])
        ->middleware('permission:members.view')
        ->name('index');

    Route::get('/{member}', [MemberController::class, 'show'])
        ->whereNumber('member')
        ->middleware('permission:members.view')
        ->name('show');

    Route::put('/{member}', [MemberController::class, 'update'])
        ->whereNumber('member')
        ->middleware('permission:members.update')
        ->name('update');

    Route::patch('/{member}/toggle-status', [MemberController::class, 'toggleStatus'])
        ->whereNumber('member')
        ->middleware('permission:members.update')
        ->name('toggleStatus');

    Route::get('/{member}/document', [MemberController::class, 'document'])
        ->whereNumber('member')
        ->middleware('permission:members.view')
        ->name('document');

    Route::get('/{member}/document/download', [MemberController::class, 'downloadDocument'])
        ->whereNumber('member')
        ->middleware('permission:members.view')
        ->name('document.download');

    Route::delete('/{member}', [MemberController::class, 'destroy'])
        ->whereNumber('member')
        ->middleware('permission:members.delete')
        ->name('destroy');
});
