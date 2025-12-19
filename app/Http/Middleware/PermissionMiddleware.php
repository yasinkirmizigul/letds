<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PermissionMiddleware
{
    /**
     * Kullanım:
     *  ->middleware('permission:blog.view')
     *  ->middleware('permission:blog.view,blog.create')  // any-of
     */
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        $user = $request->user();

        if (!$user || !$user->is_active) {
            abort(403);
        }

        // Superadmin bypass
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Any-of: verilen permissionlardan biri varsa geç
        foreach ($permissions as $permission) {
            $permission = trim((string) $permission);
            if ($permission !== '' && $user->hasPermission($permission)) {
                return $next($request);
            }
        }

        abort(403, 'Bu işlem için yetkin yok.');
    }
}
