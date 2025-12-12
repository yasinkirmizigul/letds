<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Demo1Controller extends Controller
{
    public function index()
    {
        return view('pages.app.index', [
            'pageTitle' => 'APP - Sidebar',
            'pageDescription' => 'Sidebar layout başlangıcı',
            'currentDemo' => 'demo1',
        ]);
    }
}
