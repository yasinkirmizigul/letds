<?php

namespace App\Http\Controllers\Site\Member;

use App\Http\Controllers\Controller;
use App\Models\Appointment\Appointment;
use App\Models\Member;
use App\Models\Review\ServiceReview;
use App\Services\Review\ServiceReviewAssignmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MemberAccountController extends Controller
{
    public function show(Request $request, ServiceReviewAssignmentService $assignmentService): View
    {
        /** @var Member $member */
        $member = $request->user('member');
        $assignmentService->syncCompletedServices($member->id);

        $member->loadCount([
            'appointments',
            'contactMessages',
            'serviceReviews',
            'serviceReviews as pending_service_reviews_count' => fn ($query) => $query
                ->where('status', ServiceReview::STATUS_PENDING),
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
            'pendingReviews' => $member->serviceReviews()
                ->pending()
                ->with('provider:id,name,title')
                ->latest('service_completed_at')
                ->limit(3)
                ->get(),
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
