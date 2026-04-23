<?php

namespace App\Http\Middleware;

use App\Models\Admin\AuditLog\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditRequestMiddleware
{
    private const REDACTED_KEYS = [
        'password',
        'password_confirmation',
        'token',
        'access_token',
        'refresh_token',
        'secret',
        'api_key',
        'authorization',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        /** @var Response $response */
        $response = $next($request);

        if (!$request->is('admin/*')) {
            return $response;
        }

        $user = $request->user();
        $durationMs = (int) round((microtime(true) - $start) * 1000);
        $payload = $request->except(['_token']);

        if ($request->hasFile('file') || $request->hasFile('files')) {
            $payload['__files__'] = true;
            unset($payload['file'], $payload['files']);
        }

        if (strtoupper($request->method()) === 'GET') {
            $payload = null;
        } else {
            $payload = $this->sanitizePayload($payload);
        }

        AuditLog::create([
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'user_name' => $user?->name,
            'action' => 'request',
            'route' => optional($request->route())->getName(),
            'method' => $request->method(),
            'status' => method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'uri' => $request->path(),
            'query' => $this->sanitizePayload($request->query()),
            'payload' => $payload,
            'context' => null,
            'duration_ms' => $durationMs,
        ]);

        return $response;
    }

    private function sanitizePayload(mixed $value, ?string $key = null, int $depth = 0): mixed
    {
        if ($depth > 5) {
            return '[truncated]';
        }

        if ($key !== null && in_array(strtolower($key), self::REDACTED_KEYS, true)) {
            return '[redacted]';
        }

        if (is_array($value)) {
            $sanitized = [];
            $count = 0;

            foreach ($value as $nestedKey => $nestedValue) {
                if ($count >= 50) {
                    $sanitized['__truncated__'] = true;
                    break;
                }

                $sanitized[$nestedKey] = $this->sanitizePayload($nestedValue, (string) $nestedKey, $depth + 1);
                $count++;
            }

            return $sanitized;
        }

        if (is_string($value)) {
            $value = trim($value);

            if (mb_strlen($value) > 500) {
                return mb_substr($value, 0, 500) . '...[truncated]';
            }

            return $value;
        }

        return $value;
    }
}
