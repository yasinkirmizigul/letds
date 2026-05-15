<?php

use App\Http\Controllers\Admin\TinyMceController;
use Illuminate\Support\Facades\Route;

Route::post('/tinymce/upload', [TinyMceController::class, 'upload'])
    ->middleware([
        'permission:blog.create,blog.update,projects.create,projects.update,products.create,products.update,site_pages.create,site_pages.update',
        'throttle:20,1',
    ])
    ->name('tinymce.upload');
