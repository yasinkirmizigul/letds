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
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

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
            'projects',
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
                ->with(['provider' => fn ($provider) => $provider
                    ->visibleTo(null)
                    ->select(['users.id', 'users.name', 'users.title'])])
                ->latest('service_completed_at')
                ->limit(3)
                ->get(),
            'latestProjects' => $member->projects()
                ->withCount('files')
                ->latest('updated_at')
                ->limit(3)
                ->get(),
        ]);
    }

    public function edit(Request $request): View
    {
        return view('site.account.edit', [
            'pageTitle' => 'Profil Bilgilerimi Düzenle',
            'member' => $request->user('member'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var Member $member */
        $member = $request->user('member');
        $emailChanged = mb_strtolower(trim((string) $request->input('email')))
            !== mb_strtolower((string) $member->email);
        $passwordChanged = filled($request->input('password'));

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'surname' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email:rfc',
                'max:190',
                Rule::unique('members', 'email')->ignore($member->id),
            ],
            'phone' => ['nullable', 'string', 'max:40'],
            'institution' => ['nullable', 'string', 'max:190'],
            'current_password' => [
                Rule::requiredIf($emailChanged || $passwordChanged),
                'nullable',
                'current_password:member',
            ],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        DB::transaction(function () use ($member, $validated, $emailChanged): void {
            $data = [
                'name' => trim($validated['name']),
                'surname' => trim($validated['surname']),
                'email' => mb_strtolower(trim($validated['email'])),
                'phone' => filled($validated['phone'] ?? null) ? trim($validated['phone']) : null,
                'institution' => filled($validated['institution'] ?? null) ? trim($validated['institution']) : null,
            ];

            if ($emailChanged) {
                $data['email_verified_at'] = null;
            }

            if (filled($validated['password'] ?? null)) {
                $data['password'] = $validated['password'];
            }

            $member->update($data);
        });

        return redirect()
            ->route('member.account.show')
            ->with('success', 'Profil bilgileriniz güncellendi.');
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
