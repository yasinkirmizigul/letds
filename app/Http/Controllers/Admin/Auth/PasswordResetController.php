<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin\User\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class PasswordResetController extends Controller
{
    public function showLinkRequestForm(): View
    {
        return view('admin.pages.auth.forgot-password', [
            'pageTitle' => 'Şifremi Unuttum',
        ]);
    }

    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ], [], [
            'email' => 'e-posta',
        ]);

        $email = Str::lower(trim((string) $validated['email']));

        $user = User::query()
            ->where('email', $email)
            ->first();

        if ($user && $this->canResetPassword($user)) {
            Password::broker('users')->sendResetLink([
                'email' => $email,
            ]);
        }

        return back()->with(
            'status',
            'Eğer bu e-posta ile erişilebilir bir yönetici hesabı varsa, şifre yenileme bağlantısı gönderildi.'
        );
    }

    public function showResetForm(Request $request, string $token): View
    {
        return view('admin.pages.auth.reset-password', [
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
            'password' => ['required', 'string', 'min:8', 'max:190', 'confirmed'],
        ], [
            'password.confirmed' => 'Şifre tekrarı yeni şifre ile aynı olmalı.',
        ], [
            'email' => 'e-posta',
            'password' => 'yeni şifre',
            'password_confirmation' => 'şifre tekrarı',
        ]);

        $email = Str::lower(trim((string) $validated['email']));
        $user = User::query()->where('email', $email)->first();

        if (!$user || !$this->canResetPassword($user)) {
            throw ValidationException::withMessages([
                'email' => 'Bu hesap için şifre yenileme işlemi kullanılamıyor.',
            ]);
        }

        $status = Password::broker('users')->reset(
            [
                'email' => $email,
                'password' => (string) $validated['password'],
                'password_confirmation' => (string) $request->input('password_confirmation'),
                'token' => (string) $validated['token'],
            ],
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                try {
                    DB::table('sessions')->where('user_id', $user->getKey())->delete();
                } catch (Throwable) {
                    // Session driver may not be database-backed.
                }

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => 'Şifre yenileme bağlantısı geçersiz, süresi dolmuş veya daha önce kullanılmış olabilir.',
            ]);
        }

        return redirect()
            ->route('login')
            ->with('success', 'Şifreniz güncellendi. Yeni şifrenizle yönetim paneline giriş yapabilirsiniz.');
    }

    private function canResetPassword(User $user): bool
    {
        return method_exists($user, 'canAccessBackoffice')
            ? $user->canAccessBackoffice()
            : (bool) $user->is_active;
    }
}
