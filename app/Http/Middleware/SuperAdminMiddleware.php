<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user) {
            abort(403);
        }

        if (!$user->isSuperAdmin()) {
            abort(403, 'Bu sayfaya erişim yetkin yok.');
        }

        return $next($request);
    }
}
