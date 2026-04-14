<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Appointment\AppointmentCalendarController;
use App\Http\Controllers\Admin\Appointment\AppointmentSettingsController;

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

    Route::get('/{appointment}/history', [AppointmentCalendarController::class, 'history'])
        ->middleware('permission:appointments.view')
        ->name('history');

    Route::post('/blocks', [AppointmentCalendarController::class, 'storeBlock'])
        ->middleware('permission:appointments.update')
        ->name('blocks.store');

    Route::post('/blocks/{timeOff}/move', [AppointmentCalendarController::class, 'moveBlock'])
        ->middleware('permission:appointments.update')
        ->name('blocks.move');

    Route::post('/blocks/{timeOff}/resize', [AppointmentCalendarController::class, 'resizeBlock'])
        ->middleware('permission:appointments.update')
        ->name('blocks.resize');

    Route::post('/blocks/{timeOff}/delete', [AppointmentCalendarController::class, 'deleteBlock'])
        ->middleware('permission:appointments.update')
        ->name('blocks.delete');

    Route::get('/settings', [AppointmentSettingsController::class, 'index'])
        ->middleware('permission:appointments.update')
        ->name('settings');

    Route::get('/availability', [AppointmentSettingsController::class, 'availability'])
        ->middleware('permission:appointments.view')
        ->name('availability');

    Route::get('/providers/{provider}/schedule', [AppointmentSettingsController::class, 'providerSchedule'])
        ->middleware('permission:appointments.update')
        ->name('providers.schedule');

    Route::post('/providers/{provider}/schedule', [AppointmentSettingsController::class, 'saveProviderSchedule'])
        ->middleware('permission:appointments.update')
        ->name('providers.schedule.save');

    Route::post('/providers/{provider}/time-offs', [AppointmentSettingsController::class, 'storeTimeOff'])
        ->middleware('permission:appointments.update')
        ->name('providers.timeoffs.store');

    Route::delete('/providers/{provider}/time-offs/{timeOff}', [AppointmentSettingsController::class, 'destroyTimeOff'])
        ->middleware('permission:appointments.update')
        ->name('providers.timeoffs.destroy');

    Route::get('/blackouts', [AppointmentSettingsController::class, 'listBlackouts'])
        ->middleware('permission:appointments.update')
        ->name('blackouts.list');

    Route::post('/blackouts', [AppointmentSettingsController::class, 'storeBlackout'])
        ->middleware('permission:appointments.update')
        ->name('blackouts.store');

    Route::delete('/blackouts/{blackout}', [AppointmentSettingsController::class, 'destroyBlackout'])
        ->middleware('permission:appointments.update')
        ->name('blackouts.destroy');

    Route::post('/blocks/{timeOff}/update', [AppointmentCalendarController::class, 'updateBlock'])
        ->middleware('permission:appointments.update')
        ->name('blocks.update');

    Route::get('/blocks/{timeOff}', [AppointmentCalendarController::class, 'showBlock'])
        ->middleware('permission:appointments.view')
        ->name('blocks.show');

    Route::get('/{appointment}', [AppointmentCalendarController::class, 'show'])
        ->middleware('permission:appointments.view')
        ->name('show');
});
