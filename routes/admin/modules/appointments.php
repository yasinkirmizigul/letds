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
});
