<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Appointment\AppointmentCalendarController;

Route::prefix('appointments')->as('appointments.')->group(function () {
    Route::get('/calendar', [AppointmentCalendarController::class, 'index'])
        ->middleware('permission:appointments.view')
        ->name('calendar');

    Route::get('/calendar/events', [AppointmentCalendarController::class, 'events'])
        ->middleware('permission:appointments.view')
        ->name('calendar.events');

    Route::post('/', [AppointmentCalendarController::class, 'store'])
        ->middleware('permission:appointments.create')
        ->name('store');

    Route::post('/{appointment}/transfer', [AppointmentCalendarController::class, 'transfer'])
        ->middleware('permission:appointments.update')
        ->name('transfer');

    Route::post('/{appointment}/resize', [AppointmentCalendarController::class, 'resize'])
        ->middleware('permission:appointments.update')
        ->name('resize');

    Route::post('/{appointment}/cancel', [AppointmentCalendarController::class, 'cancel'])
        ->middleware('permission:appointments.cancel')
        ->name('cancel');
});
