<?php

namespace App\Support\Audit;

use App\Models\Admin\AuditLog\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

final class AuditEvent
{
    public static function log(string $action, array $context = [], ?int $status = 200): void
    {
        $u = Auth::user();

        // user yoksa yazma (SYSTEM değil şartı)
        if (!$u) return;

        $routeName = null;
        try { $routeName = Route::currentRouteName(); } catch (\Throwable) {}

        AuditLog::create([
            'user_id'     => $u->id ?? null,
            'user_email'  => $u->email ?? null,
            'user_name'   => $u->name ?? null,

            'action'      => $action,
            'route'       => $routeName,
            'method'      => request()->method(),
            'status'      => $status,

            'ip'          => request()->ip(),
            'user_agent'  => request()->userAgent(),

            'uri'         => request()->path(),
            'query'       => request()->query(),
            'payload'     => null,
            'context'     => $context,

            'duration_ms' => null,
        ]);
    }
}
