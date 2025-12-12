<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Demo1Controller;
use App\Http\Controllers\Demo2Controller;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Demo 1 routes
Route::get('/', function () {
    return view('pages.app.index');
});

