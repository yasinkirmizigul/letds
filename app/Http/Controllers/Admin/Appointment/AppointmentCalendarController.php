<?php

namespace App\Http\Controllers\Admin\Appointment;

use App\Http\Controllers\Controller;
use App\Models\Admin\User\User;
use Illuminate\Http\Request;

class AppointmentCalendarController extends Controller
{
    public function index(Request $request)
    {
        // Provider listesi: şimdilik User tablosundan
        $providers = User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.pages.appointments.calendar', compact('providers'));
    }

    public function events(Request $request)
    {
        // DB yok -> şimdilik BOŞ
        return response()->json([]);
    }
}
