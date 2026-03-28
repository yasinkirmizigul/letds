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

    /**
     * UTC "şimdi"
     */
    public static function nowUtc(): Carbon
    {
        return now()->utc();
    }

    /**
     * Istanbul "şimdi"
     */
    public static function nowLocal(): Carbon
    {
        return now(self::APP_TZ);
    }

    /**
     * Veriyi UTC'ye normalize et
     */
    public static function toUtc(
        CarbonInterface|string|null $value,
        ?string $sourceTz = null
    ): ?Carbon {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return Carbon::instance($value)->utc();
        }

        return Carbon::parse($value, $sourceTz ?: self::APP_TZ)->utc();
    }

    /**
     * Veriyi local timezone'a çevir
     */
    public static function toLocal(
        CarbonInterface|string|null $value,
        ?string $targetTz = null
    ): ?Carbon {
        if ($value === null || $value === '') {
            return null;
        }

        $tz = $targetTz ?: self::APP_TZ;

        if ($value instanceof CarbonInterface) {
            return Carbon::instance($value)->setTimezone($tz);
        }

        return Carbon::parse($value)->setTimezone($tz);
    }

    /**
     * datetime-local input için güvenli parse
     * Örn: 2026-03-31T12:30
     */
    public static function localInputToUtc(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d\TH:i', $value, self::APP_TZ)->utc();
    }

    /**
     * time kolonları için normalize
     * 10:00 -> 10:00:00
     */
    public static function normalizeTimeString(?string $time): ?string
    {
        if ($time === null || trim($time) === '') {
            return null;
        }

        $time = trim($time);

        return strlen($time) === 5 ? $time . ':00' : $time;
    }

    /**
     * UI formatı
     */
    public static function formatLocal(
        CarbonInterface|string|null $value,
        string $format = 'd.m.Y H:i'
    ): ?string {
        $dt = self::toLocal($value);

        return $dt?->format($format);
    }
}
