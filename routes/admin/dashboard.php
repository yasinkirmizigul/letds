<?php

use App\Http\Controllers\Admin\Dash\DashController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashController::class, 'index'])->name('dashboard');
Route::get('/dashboard/manage', [DashController::class, 'manage'])->name('dashboard.manage');
Route::put('/dashboard/manage', [DashController::class, 'updatePreferences'])->name('dashboard.manage.update');
