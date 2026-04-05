<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class DateTimeHelper
{
    public const APP_TZ = 'Europe/Istanbul';

    public static function tz(): string
    {
        return self::APP_TZ;
    }

    public static function now(): Carbon
    {
        return now(self::APP_TZ);
    }

    public static function parse(
        CarbonInterface|string|null $value
    ): ?Carbon {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return Carbon::instance($value)->setTimezone(self::APP_TZ);
        }

        return Carbon::parse($value, self::APP_TZ);
    }

    public static function localInput(?string $value): ?Carbon
    {
        if (!$value) return null;

        return Carbon::createFromFormat('Y-m-d\TH:i', $value, self::APP_TZ);
    }

    public static function normalizeTimeString(?string $time): ?string
    {
        if (!$time) return null;

        return strlen($time) === 5 ? $time . ':00' : $time;
    }

    public static function format(
        CarbonInterface|string|null $value,
        string $format = 'd.m.Y H:i'
    ): ?string {
        return self::parse($value)?->format($format);
    }
}
