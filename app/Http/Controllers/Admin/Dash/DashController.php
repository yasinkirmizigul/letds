<?php

namespace App\Http\Controllers\Admin\Dash;


use App\Http\Controllers\Controller;

class DashController extends Controller
{
    public function index()
    {
        return view('admin.pages.dash.index', [
            'pageTitle' => 'Dashboard',
            'pageDescription' => 'Admin Dashboard',
        ]);
    }
}
