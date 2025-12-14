<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = auth()->user();

        if (!$user || !$user->is_active) {
            abort(403);
        }

        if (!$user->hasPermission($permission)) {
            abort(403, 'Bu işlem için yetkin yok.');
        }

        return $next($request);
    }
}
