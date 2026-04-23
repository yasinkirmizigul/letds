<?php

namespace App\Http\Middleware;

use App\Support\Site\SiteLocalization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SiteLocaleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeLocale = $request->route('locale');
        $queryLocale = $request->query('site_locale');
        $routeName = (string) optional($request->route())->getName();
        $defaultLocale = SiteLocalization::defaultLocale();

        if (filled($routeLocale)) {
            $language = SiteLocalization::findLanguage((string) $routeLocale);
            abort_unless($language, 404);

            $locale = $language->code;
            $request->session()->put('site_locale', $locale);
        } elseif (filled($queryLocale) && SiteLocalization::findLanguage((string) $queryLocale)) {
            $locale = (string) $queryLocale;
            $request->session()->put('site_locale', $locale);
        } elseif (in_array($routeName, ['site.home', 'site.pages.show'], true)) {
            $locale = $defaultLocale;
        } else {
            $sessionLocale = (string) $request->session()->get('site_locale', $defaultLocale);
            $locale = SiteLocalization::findLanguage($sessionLocale)?->code ?: $defaultLocale;
        }

        app()->setLocale($locale);
        app()->instance('site.current_locale', $locale);
        app()->instance('site.current_language', SiteLocalization::findLanguage($locale) ?? SiteLocalization::defaultLanguage());

        return $next($request);
    }
}
