<?php

use App\Http\Controllers\Site\Appointment\AppointmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['site.locale', 'auth:member', 'member.active'])->group(function () {
    Route::get('/randevu-al', [AppointmentController::class, 'index'])
        ->name('member.appointments.index');

    Route::prefix('member/appointments')->name('member.appointments.')->group(function () {
        Route::get('/', [AppointmentController::class, 'index'])->name('home');
        Route::get('/availability', [AppointmentController::class, 'availability'])->name('availability');
        Route::get('/days', [AppointmentController::class, 'days'])->name('days');
        Route::post('/', [AppointmentController::class, 'store'])->name('store');
        Route::post('/{id}/cancel', [AppointmentController::class, 'cancel'])->name('cancel');
        Route::post('/{id}/reschedule', [AppointmentController::class, 'reschedule'])->name('reschedule');
    });
});
