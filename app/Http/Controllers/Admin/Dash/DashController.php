<?php

namespace App\Http\Controllers\Admin\Dash;


use App\Http\Controllers\Controller;

class DashController extends Controller
{
    public function index()
    {
        return view('admin.pages.dash.index', [
            'pageTitle' => 'APP - Sidebar',
            'pageDescription' => 'Sidebar layout başlangıcı',
            'currentDemo' => 'demo1',
        ]);
    }
}
