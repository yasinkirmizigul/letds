<?php

namespace App\Support\Site;

use App\Models\Site\SiteLanguage;
use App\Models\Site\SitePage;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SiteLocalization
{
    public static function languages(bool $activeOnly = true): Collection
    {
        $query = SiteLanguage::query()->ordered();

        if ($activeOnly) {
            $query->active();
        }

        return $query->get();
    }

    public static function defaultLanguage(): SiteLanguage
    {
        return SiteLanguage::query()
            ->where('is_default', true)
            ->first()
            ?? SiteLanguage::query()->ordered()->firstOrFail();
    }

    public static function defaultLocale(): string
    {
        return self::defaultLanguage()->code;
    }

    public static function currentLocale(): string
    {
        $locale = app()->bound('site.current_locale')
            ? (string) app('site.current_locale')
            : (string) app()->getLocale();

        return $locale !== '' ? $locale : self::defaultLocale();
    }

    public static function currentLanguage(): SiteLanguage
    {
        return app()->bound('site.current_language')
            ? app('site.current_language')
            : (self::findLanguage(self::currentLocale()) ?? self::defaultLanguage());
    }

    public static function findLanguage(?string $code, bool $activeOnly = true): ?SiteLanguage
    {
        $code = trim((string) $code);

        if ($code === '') {
            return null;
        }

        return self::languages($activeOnly)->firstWhere('code', $code);
    }

    public static function isDefault(?string $locale = null): bool
    {
        $locale = trim((string) ($locale ?: self::currentLocale()));

        return $locale === self::defaultLocale();
    }

    public static function homeUrl(?string $locale = null): string
    {
        $locale = trim((string) ($locale ?: self::currentLocale()));

        if ($locale === '' || self::isDefault($locale)) {
            return route('site.home');
        }

        return route('site.home.localized', ['locale' => $locale]);
    }

    public static function switchUrl(Request $request, string $targetLocale, ?SitePage $page = null): string
    {
        if ($page) {
            return $page->publicUrl($targetLocale);
        }

        if ($request->routeIs('site.home', 'site.home.localized')) {
            return self::homeUrl($targetLocale);
        }

        return $request->fullUrlWithQuery([
            'site_locale' => $targetLocale,
        ]);
    }
}
