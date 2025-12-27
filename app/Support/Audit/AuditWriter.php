<?php

namespace App\Support\Audit;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AuditWriter
{
    /**
     * CLI / System kaynaklı audit kaydı.
     * Kolonlar farklıysa patlamasın diye tablo kolonlarına göre filtreler.
     */
    public static function system(string $action, array $meta = [], ?int $userId = null, array $extra = []): void
    {
        // Tablo adın farklıysa burada değiştir
        $table = 'audit_logs';

        if (!Schema::hasTable($table)) {
            // Audit sistemi yoksa uygulama patlamasın diye sessiz çık.
            return;
        }

        $columns = Schema::getColumnListing($table);

        $row = array_merge([
            // en yaygın kolon isimleri
            'user_id'     => $userId,
            'action'      => $action,
            'method'      => 'CLI',
            'url'         => 'artisan',
            'route'       => null,
            'ip'          => null,
            'user_agent'  => 'CLI',
            'is_system'   => 1,

            // JSON/meta kolonları genelde string/json
            'meta'        => $meta,
            'payload'     => $meta,
            'context'     => $meta,
            'data'        => $meta,

            'created_at'  => now(),
            'updated_at'  => now(),
        ], $extra);

        // Sadece tabloda gerçekten olan kolonları gönder
        $filtered = [];
        foreach ($row as $k => $v) {
            if (!in_array($k, $columns, true)) continue;

            // JSON olmayan kolonlara array basmayalım
            if (is_array($v)) {
                $filtered[$k] = json_encode($v, JSON_UNESCAPED_UNICODE);
            } else {
                $filtered[$k] = $v;
            }
        }

        // Hiç eşleşen kolon yoksa boş insert denemeyelim
        if (!$filtered) return;

        DB::table($table)->insert($filtered);
    }
}
