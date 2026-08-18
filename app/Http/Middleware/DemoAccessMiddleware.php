<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoAccessMiddleware
{
    private const LOOPBACK_HOSTS = ['127.0.0.1', 'localhost', '::1'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('demo.access.enabled') || $this->isLocalLoopbackRequest($request)) {
            return $next($request);
        }

        $username = (string) config('demo.access.username');
        $password = (string) config('demo.access.password');

        if ($username === '' || $password === '') {
            return response('Demo erisim bilgileri yapilandirilmadi.', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $credentialsAreValid = is_string($request->getUser())
            && is_string($request->getPassword())
            && hash_equals($username, $request->getUser())
            && hash_equals($password, $request->getPassword());

        if (! $credentialsAreValid) {
            return response('Bu demo kullanici adi ve parola ile korunuyor.', Response::HTTP_UNAUTHORIZED)
                ->header('WWW-Authenticate', 'Basic realm="LETDS Demo", charset="UTF-8"')
                ->header('Cache-Control', 'private, no-store')
                ->header('X-Robots-Tag', 'noindex, nofollow, noarchive');
        }

        return $next($request)
            ->header('Cache-Control', 'private, no-store')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }

    private function isLocalLoopbackRequest(Request $request): bool
    {
        $host = trim(strtolower($request->getHost()), '[]');
        $remoteAddress = trim(strtolower((string) $request->server('REMOTE_ADDR')), '[]');

        return in_array($host, self::LOOPBACK_HOSTS, true)
            && in_array($remoteAddress, self::LOOPBACK_HOSTS, true);
    }
}
