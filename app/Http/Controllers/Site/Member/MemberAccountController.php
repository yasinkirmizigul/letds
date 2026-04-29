<?php

namespace App\Http\Controllers\Site\Member;

use App\Http\Controllers\Controller;
use App\Models\Appointment\Appointment;
use App\Models\Member;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MemberAccountController extends Controller
{
    public function show(Request $request): View
    {
        /** @var Member $member */
        $member = $request->user('member');

        $member->loadCount([
            'appointments',
            'contactMessages',
            'appointments as active_appointments_count' => fn ($query) => $query
                ->where('status', Appointment::STATUS_BOOKED)
                ->where('end_at', '>=', now()),
        ]);

        return view('site.account.show', [
            'pageTitle' => 'Üyelik Hesabım',
            'member' => $member,
            'hasUpcomingAppointment' => $member->appointments()
                ->where('status', Appointment::STATUS_BOOKED)
                ->where('end_at', '>=', now())
                ->exists(),
        ]);
    }

    public function terminate(Request $request): RedirectResponse
    {
        /** @var Member $member */
        $member = $request->user('member');

        $request->validate([
            'current_password' => ['required', 'current_password:member'],
            'confirm_termination' => ['accepted'],
        ], [
            'confirm_termination.accepted' => 'Üyeliği sonlandırma onayını vermeniz gerekiyor.',
        ]);

        $hasUpcomingAppointment = $member->appointments()
            ->where('status', Appointment::STATUS_BOOKED)
            ->where('end_at', '>=', now())
            ->exists();

        if ($hasUpcomingAppointment) {
            return back()->withErrors([
                'termination' => 'Yaklaşan randevunuz bulunduğu için üyeliğinizi şimdi sonlandıramazsınız. Önce randevularınızı kapatın.',
            ]);
        }

        DB::transaction(function () use ($member) {
            $member->forceFill([
                'is_active' => false,
                'suspended_at' => now(),
                'suspension_reason' => 'Üyelik sahibi tarafından sonlandırıldı.',
                'membership_ended_at' => now(),
                'remember_token' => Str::random(60),
            ])->save();
        });

        Auth::guard('member')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('member.login')
            ->with('success', 'Üyeliğiniz pasife alındı. Bilgileriniz güvenli şekilde saklanmaya devam edecektir.');
    }
}
