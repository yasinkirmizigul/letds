<?php

use App\Http\Controllers\Admin\Search\AdminQuickSearchController;
use Illuminate\Support\Facades\Route;

Route::get('/quick-search', AdminQuickSearchController::class)
    ->middleware('throttle:90,1')
    ->name('quick-search');
