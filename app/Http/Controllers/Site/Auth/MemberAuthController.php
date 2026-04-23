<?php

namespace App\Http\Controllers\Site\Auth;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Services\Member\MemberDocumentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MemberAuthController extends Controller
{
    public function __construct(
        private readonly MemberDocumentService $documentService
    ) {
    }

    public function showLogin(): View
    {
        return view('site.auth.member-login', [
            'pageTitle' => 'Üye Girişi',
        ]);
    }

    public function showRegister(): View
    {
        return view('site.auth.member-register', [
            'pageTitle' => 'Üye Kaydı',
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'surname' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:190', 'unique:members,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'filepath' => ['nullable', 'file', 'max:12288', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx'],
        ]);

        /** @var Member $member */
        $member = DB::transaction(function () use ($validated, $request) {
            $member = Member::create([
                'name' => trim((string) $validated['name']),
                'surname' => trim((string) $validated['surname']),
                'email' => Str::lower(trim((string) $validated['email'])),
                'phone' => filled($validated['phone'] ?? null) ? trim((string) $validated['phone']) : null,
                'password' => (string) $validated['password'],
                'is_active' => true,
            ]);

            if ($request->hasFile('filepath')) {
                $this->documentService->sync($member, $request->file('filepath'));
            }

            return $member;
        });

        Auth::guard('member')->login($member);
        $request->session()->regenerate();

        return redirect()
            ->route('member.appointments.index')
            ->with('success', 'Üyelik kaydınız oluşturuldu. Randevu paneline yönlendirildiniz.');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $email = Str::lower(trim((string) $data['email']));
        $member = Member::withTrashed()
            ->where('email', $email)
            ->first();

        if ($member?->trashed()) {
            throw ValidationException::withMessages([
                'email' => 'Bu üyelik kaydı kaldırılmış durumda. Yeni bir kayıt oluşturabilirsiniz.',
            ]);
        }

        if ($member && !$member->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Üyeliğiniz şu anda askıya alınmış durumda. Lütfen site yöneticisi ile iletişime geçin.',
            ]);
        }

        if (!Auth::guard('member')->attempt([
            'email' => $email,
            'password' => $data['password'],
            'is_active' => 1,
        ], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'E-posta veya şifre hatalı.',
            ]);
        }

        $request->session()->regenerate();

        /** @var Member|null $authenticatedMember */
        $authenticatedMember = Auth::guard('member')->user();
        $authenticatedMember?->forceFill([
            'last_login_at' => now(),
        ])->save();

        return redirect()->route('member.appointments.index');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('member')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('member.login');
    }
}
