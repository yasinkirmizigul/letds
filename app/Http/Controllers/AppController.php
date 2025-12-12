<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AppController extends Controller
{
    public function index()
    {
        return view('app.pages.app.index', [
            'pageTitle' => 'APP - Sidebar',
            'pageDescription' => 'Sidebar layout başlangıcı',
            'currentDemo' => 'demo1',
        ]);
    }
}
