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

        // admin veya superadmin
        if (!$user->isAdmin()) {
            abort(403, 'Bu alan sadece admin yetkisine açıktır.');
        }

        return $next($request);
    }
}

