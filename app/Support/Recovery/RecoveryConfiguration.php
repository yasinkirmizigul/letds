<?php

namespace App\Support\Recovery;

use RuntimeException;

class RecoveryConfiguration
{
    public function isReady(): bool
    {
        return $this->problems() === [];
    }

    /** @return list<string> */
    public function problems(): array
    {
        $problems = [];

        if (strlen((string) config('backup.backup.password')) < 32) {
            $problems[] = 'BACKUP_ARCHIVE_PASSWORD en az 32 karakter olmalı.';
        }

        foreach (['key', 'secret', 'bucket'] as $field) {
            if (blank(config("filesystems.disks.recovery_backups.{$field}"))) {
                $problems[] = "Yedek bulutu için {$field} ayarı eksik.";
            }
        }

        if (! is_file(base_path('.env'))) {
            $problems[] = '.env bulunamadı; APP_KEY geri yüklemesi güvenceye alınamaz.';
        }

        return $problems;
    }

    public function ensureReady(): void
    {
        if ($problems = $this->problems()) {
            throw new RuntimeException(implode(' ', $problems));
        }
    }
}
