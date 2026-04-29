<?php

namespace App\Http\Controllers\Site\Auth;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MemberPasswordResetController extends Controller
{
    public function showLinkRequestForm(): View
    {
        return view('site.auth.member-forgot-password', [
            'pageTitle' => 'Şifremi Unuttum',
        ]);
    }

    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = Str::lower(trim((string) $validated['email']));

        $member = Member::query()
            ->where('email', $email)
            ->first();

        if (!$member || !$member->is_active) {
            return back()->with('status', 'Eğer bu e-posta ile aktif bir üyelik varsa, şifre yenileme bağlantısı gönderildi.');
        }

        Password::broker('members')->sendResetLink([
            'email' => $email,
        ]);

        return back()->with('status', 'Eğer bu e-posta ile aktif bir üyelik varsa, şifre yenileme bağlantısı gönderildi.');
    }

    public function showResetForm(Request $request, string $token): View
    {
        return view('site.auth.member-reset-password', [
            'pageTitle' => 'Şifre Yenile',
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $email = Str::lower(trim((string) $validated['email']));
        $member = Member::query()->where('email', $email)->first();

        if (!$member || !$member->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Bu üyelik için şifre yenileme işlemi kullanılamıyor.',
            ]);
        }

        $status = Password::broker('members')->reset(
            [
                'email' => $email,
                'password' => (string) $validated['password'],
                'password_confirmation' => (string) $request->input('password_confirmation'),
                'token' => (string) $validated['token'],
            ],
            function (Member $member, string $password) {
                $member->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($member));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => 'Şifre yenileme bağlantısı geçersiz, süresi dolmuş veya daha önce kullanılmış olabilir.',
            ]);
        }

        return redirect()
            ->route('member.login')
            ->with('success', 'Şifreniz güncellendi. Yeni şifrenizle giriş yapabilirsiniz.');
    }
}
