<?php

namespace App\Models\Concerns;

use App\Support\DateTimeHelper;

trait HasLocalDateTimes
{
    public function asLocalDateTime(?string $attribute)
    {
        $value = $this->{$attribute} ?? null;

        return $value ? DateTimeHelper::toLocal($value) : null;
    }

    public function asLocalFormatted(?string $attribute, string $format = 'd.m.Y H:i'): ?string
    {
        $value = $this->{$attribute} ?? null;

        return $value ? DateTimeHelper::formatLocal($value, $format) : null;
    }
}
