<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user) {
            abort(403);
        }

        if (!method_exists($user, 'canAccessBackoffice') || !$user->canAccessBackoffice()) {
            abort(403, 'Bu alan sadece yetkili panel kullanicilarina aciktir.');
        }

        return $next($request);
    }
}
