<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoAccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('demo.access.enabled')) {
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
}
