<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class Rbac
{
    public static function bumpVersion(): int
    {
        if (!Cache::has('rbac:version')) {
            Cache::forever('rbac:version', 1);
            return 1;
        }

        return (int) Cache::increment('rbac:version');
    }
}
