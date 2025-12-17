<?php

namespace App\Support;

class Sluggable
{
    public static function slugifyTR(string $str): string
    {
        $str = trim(mb_strtolower($str));

        $map = [
            'ğ'=>'g','ü'=>'u','ş'=>'s','ı'=>'i','ö'=>'o','ç'=>'c',
        ];
        $str = strtr($str, $map);

        // aksan temizle
        $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str) ?: $str;

        // izinli karakterler
        $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
        $str = preg_replace('/\s+/', '-', $str);
        $str = preg_replace('/-+/', '-', $str);
        $str = trim($str, '-');

        return $str;
    }
}
