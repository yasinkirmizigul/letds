<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('admin.pages.auth.index', [
            'pageTitle' => 'Giriş Yap',
        ]);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $remember = (bool) ($validated['remember'] ?? false);

        if (!auth()->attempt(
            ['email' => $validated['email'], 'password' => $validated['password']],
            $remember
        )) {
            throw ValidationException::withMessages([
                'email' => 'E-posta veya şifre hatalı.',
            ]);
        }

        // ✅ BURADA: kullanıcı artık auth oldu
        $user = auth()->user();

        // Pasif kullanıcı kontrolü
        if (!$user || !$user->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Hesabınız pasif. Lütfen yönetici ile iletişime geçin.',
            ]);
        }

        // ✅ Bundan sonra session’ı güvenli şekilde yenile
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
}
