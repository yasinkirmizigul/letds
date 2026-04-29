<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveMemberSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $member = $request->user('member');

        if ($member && (!$member->is_active || $member->trashed())) {
            Auth::guard('member')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('member.login', ['site_locale' => app()->getLocale()])
                ->withErrors([
                    'email' => 'Üyelik hesabınız şu anda aktif değil. Giriş için destek ekibiyle iletişime geçebilirsiniz.',
                ]);
        }

        return $next($request);
    }
}
