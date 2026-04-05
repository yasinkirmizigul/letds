<?php

namespace App\Models\Concerns;

trait HasLocalDateTimes
{
    public function asLocalDateTime(?string $attribute)
    {
        return $this->{$attribute} ?? null;
    }

    public function asLocalFormatted(?string $attribute, string $format = 'd.m.Y H:i'): ?string
    {
        $value = $this->{$attribute} ?? null;

        return $value ? $value->format($format) : null;
    }
}
