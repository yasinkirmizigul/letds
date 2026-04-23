<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('admin.pages.auth.index', [
            'pageTitle' => 'Giris Yap',
        ]);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $this->ensureIsNotRateLimited($request);

        $email = Str::lower(trim((string) $validated['email']));
        $remember = (bool) ($validated['remember'] ?? false);

        if (!auth()->attempt(
            ['email' => $email, 'password' => $validated['password']],
            $remember
        )) {
            RateLimiter::hit($this->throttleKey($request), 60);

            throw ValidationException::withMessages([
                'email' => 'E-posta veya sifre hatali.',
            ]);
        }

        $user = auth()->user();

        if (!$user || !method_exists($user, 'canAccessBackoffice') || !$user->canAccessBackoffice()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            RateLimiter::hit($this->throttleKey($request), 60);

            throw ValidationException::withMessages([
                'email' => 'E-posta veya sifre hatali.',
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function ensureIsNotRateLimited(Request $request): void
    {
        $key = $this->throttleKey($request);

        if (!RateLimiter::tooManyAttempts($key, 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            'email' => "Cok fazla giris denemesi yapildi. {$seconds} saniye sonra tekrar deneyin.",
        ]);
    }

    private function throttleKey(Request $request): string
    {
        $email = Str::lower(trim((string) $request->input('email', '')));

        return 'admin-login:' . sha1($email . '|' . $request->ip());
    }
}
