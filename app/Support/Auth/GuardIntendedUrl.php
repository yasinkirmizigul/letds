<?php

namespace App\Support\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class GuardIntendedUrl
{
    private const SESSION_KEY = 'url.intended';

    /**
     * Consume Laravel's shared intended URL only when it belongs to the active guard.
     *
     * @param  list<string>  $allowedPathPrefixes
     */
    public static function pull(Request $request, array $allowedPathPrefixes): ?string
    {
        $intendedUrl = $request->session()->pull(self::SESSION_KEY);

        if (! is_string($intendedUrl) || trim($intendedUrl) === '') {
            return null;
        }

        $parts = parse_url($intendedUrl);

        if ($parts === false || ! self::isSameApplication($request, $parts)) {
            return null;
        }

        $path = '/'.ltrim((string) ($parts['path'] ?? ''), '/');

        foreach ($allowedPathPrefixes as $prefix) {
            $prefix = '/'.trim($prefix, '/');

            if ($path === $prefix || Str::startsWith($path, $prefix.'/')) {
                return $intendedUrl;
            }
        }

        return null;
    }

    /**
     * @param  array<string, int|string>  $parts
     */
    private static function isSameApplication(Request $request, array $parts): bool
    {
        $scheme = Str::lower((string) ($parts['scheme'] ?? ''));

        if ($scheme !== '' && ! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        $host = Str::lower((string) ($parts['host'] ?? ''));

        return $host === '' || hash_equals(Str::lower($request->getHost()), $host);
    }
}
