<?php

namespace App\Http\Middleware;

use App\Models\Admin\AuditLog\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditRequestMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        /** @var Response $response */
        $response = $next($request);

        // Sadece admin tarafı (zaten admin grubuna taktık ama ekstra güvenlik)
        if (!$request->is('admin/*')) {
            return $response;
        }

        // Gürültü: tinymce upload / media upload vs istersen filtrele
        // if ($request->is('admin/tinymce/upload')) return $response;

        $u = $request->user();
        $durationMs = (int) round((microtime(true) - $start) * 1000);

        // payload sanitize
        $payload = $request->except([
            'password','password_confirmation',
            '_token','token','access_token','refresh_token',
        ]);

        // Upload isteklerinde payload şişmesin
        if ($request->hasFile('file') || $request->hasFile('files')) {
            $payload['__files__'] = true;
            unset($payload['file'], $payload['files']);
        }

        // GET’te payload’ı boş bırak (log şişmesin)
        if (strtoupper($request->method()) === 'GET') {
            $payload = null;
        }

        AuditLog::create([
            'user_id' => $u?->id,
            'user_email' => $u?->email,
            'user_name' => $u?->name,

            'action' => 'request',
            'route' => optional($request->route())->getName(),
            'method' => $request->method(),
            'status' => method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null,

            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),

            'uri' => $request->path(),
            'query' => $request->query(),
            'payload' => $payload,
            'context' => null,

            'duration_ms' => $durationMs,
        ]);

        return $response;
    }
}
