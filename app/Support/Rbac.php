<?php
namespace App\Support;

use Illuminate\Support\Facades\Cache;

class Rbac
{
    public static function bumpVersion(): void
    {
        Cache::increment('rbac:version');
    }
}
