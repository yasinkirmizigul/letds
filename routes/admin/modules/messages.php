<?php

use App\Http\Controllers\Admin\Message\ContactMessageController;
use Illuminate\Support\Facades\Route;

Route::prefix('messages')->as('messages.')->group(function () {
    Route::get('/', [ContactMessageController::class, 'index'])->name('index');
    Route::get('/{contactMessage}', [ContactMessageController::class, 'show'])
        ->whereNumber('contactMessage')
        ->name('show');
});
