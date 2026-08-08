<?php

namespace App\Support\Site;

use InvalidArgumentException;

class SiteNavigationRoutes
{
    public const HOME = 'site.home';

    public const BLOG = 'site.blog.index';

    public const GALLERIES = 'site.galleries.index';

    public const CONTACT = 'site.contact-messages.create';

    public static function options(): array
    {
        return [
            self::HOME => 'Ana Sayfa',
            self::BLOG => 'Blog',
            self::GALLERIES => 'Galeri',
            self::CONTACT => 'İletişim',
        ];
    }

    public static function isSupported(?string $routeName): bool
    {
        return is_string($routeName) && array_key_exists($routeName, self::options());
    }

    public static function resolve(string $routeName, ?string $locale = null): string
    {
        if (! self::isSupported($routeName)) {
            throw new InvalidArgumentException("Desteklenmeyen site menü rotası: {$routeName}");
        }

        $locale = $locale ?: SiteLocalization::currentLocale();

        return match ($routeName) {
            self::HOME => SiteLocalization::homeUrl($locale),
            self::BLOG, self::GALLERIES => SiteLocalization::localizedRoute(
                $routeName,
                locale: $locale,
            ),
            self::CONTACT => route($routeName, ['site_locale' => $locale]),
        };
    }
}
