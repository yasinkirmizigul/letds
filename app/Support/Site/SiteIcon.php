<?php

namespace App\Support\Site;

use Illuminate\Support\Str;

final class SiteIcon
{
    private const FONT_AWESOME_ICONS = [
        'fa-arrow-left',
        'fa-arrow-right',
        'fa-book-open',
        'fa-brain',
        'fa-briefcase',
        'fa-calendar-check',
        'fa-calendar-days',
        'fa-chart-column',
        'fa-chart-line',
        'fa-check',
        'fa-chevron-down',
        'fa-chevron-left',
        'fa-chevron-right',
        'fa-circle',
        'fa-circle-check',
        'fa-circle-info',
        'fa-circle-question',
        'fa-circle-user',
        'fa-clock',
        'fa-comments',
        'fa-compass-drafting',
        'fa-file-lines',
        'fa-heart',
        'fa-heart-pulse',
        'fa-house',
        'fa-image',
        'fa-life-ring',
        'fa-magnifying-glass',
        'fa-note-sticky',
        'fa-pen',
        'fa-rotate',
        'fa-shield-halved',
        'fa-sliders',
        'fa-users',
        'fa-wand-magic-sparkles',
        'fa-xmark',
    ];

    private const KEEN_TO_FONT_AWESOME = [
        'ki-home' => 'fa-house',
        'ki-messages' => 'fa-comments',
        'ki-notepad' => 'fa-note-sticky',
        'ki-picture' => 'fa-image',
        'ki-briefcase' => 'fa-briefcase',
        'ki-people' => 'fa-users',
        'ki-message-question' => 'fa-circle-question',
    ];

    public static function classes(?string $iconClasses): string
    {
        $tokens = preg_split('/\s+/', trim((string) $iconClasses), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $fontAwesomeIcon = collect($tokens)->first(
            fn (string $token): bool => Str::startsWith($token, 'fa-')
                && ! in_array($token, ['fa-solid', 'fa-regular', 'fa-brands'], true)
        );

        if (is_string($fontAwesomeIcon) && in_array($fontAwesomeIcon, self::FONT_AWESOME_ICONS, true)) {
            return 'fa-solid '.$fontAwesomeIcon;
        }

        foreach ($tokens as $token) {
            if (isset(self::KEEN_TO_FONT_AWESOME[$token])) {
                return 'fa-solid '.self::KEEN_TO_FONT_AWESOME[$token];
            }
        }

        return 'fa-solid fa-circle';
    }
}
