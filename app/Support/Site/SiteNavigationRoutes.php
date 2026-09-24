<?php

namespace App\Support\Site;

use InvalidArgumentException;

class SiteNavigationRoutes
{
    public const HOME = 'site.home';

    public const BLOG = 'site.blog.index';

    public const GALLERIES = 'site.galleries.index';

    public const CONTACT = 'site.contact-messages.create';

    public const FAQS = 'site.faqs.index';

    public const SERVICES = 'site.services.index';

    public const SERVICES_OFFER = 'site.services.offer';

    public const SERVICES_PROCESS = 'site.services.process';

    public const SERVICES_START = 'site.services.start';

    public static function options(): array
    {
        return [
            self::HOME => 'Ana Sayfa',
            self::FAQS => 'Sıkça Sorulan Sorular',
            self::SERVICES => 'Hizmetler',
            self::SERVICES_OFFER => 'Neler Sunuyoruz?',
            self::SERVICES_PROCESS => 'Nasıl İlerliyoruz?',
            self::SERVICES_START => 'Birlikte Başlayalım',
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
            self::FAQS, self::SERVICES => SiteLocalization::localizedRoute(
                $routeName,
                locale: $locale,
            ),
            self::SERVICES_OFFER => SiteLocalization::localizedRoute(self::SERVICES, locale: $locale).'#hizmetler',
            self::SERVICES_PROCESS => SiteLocalization::localizedRoute(self::SERVICES, locale: $locale).'#nasil-ilerliyoruz',
            self::SERVICES_START => SiteLocalization::localizedRoute(self::SERVICES, locale: $locale).'#birlikte-baslayalim',
            self::CONTACT => route($routeName, ['site_locale' => $locale]),
        };
    }
}
