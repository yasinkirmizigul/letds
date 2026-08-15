<?php

namespace App\Support\Admin;

final class AdminRoleProfile
{
    private const OPERATIONAL_PREFIXES = [
        'admin.',
        'appointments.',
        'blog.',
        'categories.',
        'ecommerce_orders.',
        'ecommerce_inventory.',
        'ecommerce_coupons.',
        'ecommerce_invoices.',
        'ecommerce_webhooks.',
        'galleries.',
        'home_sliders.',
        'media.',
        'members.',
        'messages.',
        'notifications.',
        'permissions.',
        'products.',
        'projects.',
        'roles.',
        'service_reviews.',
        'site_counters.',
        'site_faqs.',
        'site_homepage.',
        'site_languages.',
        'site_navigation.',
        'site_pages.',
        'site_payments.',
        'site_settings.',
        'trash.',
        'users.',
        'audit-logs.',
    ];

    public static function allows(string $permission): bool
    {
        $permission = trim($permission);

        if ($permission === '') {
            return false;
        }

        foreach (self::OPERATIONAL_PREFIXES as $prefix) {
            if (str_starts_with($permission, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
