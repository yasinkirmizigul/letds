<?php

namespace App\Support\Recovery;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class RecoveryArchives
{
    public function directory(): string
    {
        return trim((string) config('backup.backup.name'), '/');
    }

    public function path(string $filename): string
    {
        if (! preg_match('/\A[A-Za-z0-9][A-Za-z0-9._-]*\.zip\z/', $filename)
            || str_contains($filename, '..')) {
            throw new RuntimeException('Geçersiz yedek dosyası adı.');
        }

        return $this->directory().'/'.$filename;
    }

    /** @return list<array{name: string, path: string, size: int, modified: int}> */
    public function all(): array
    {
        $disk = Storage::disk('recovery_backups');
        $archives = [];

        foreach ($disk->files($this->directory()) as $path) {
            $name = basename($path);
            if (! Str::endsWith($name, '.zip')) {
                continue;
            }

            try {
                if ($this->path($name) !== $path) {
                    continue;
                }
            } catch (RuntimeException) {
                continue;
            }

            $archives[] = [
                'name' => $name,
                'path' => $path,
                'size' => $disk->size($path),
                'modified' => $disk->lastModified($path),
            ];
        }

        usort($archives, fn (array $a, array $b) => $b['modified'] <=> $a['modified']);

        return $archives;
    }

    public function import(UploadedFile $file, RecoveryConfiguration $configuration): string
    {
        $configuration->ensureReady();
        $this->validateArchive($file->getRealPath());

        $filename = 'import-'.now()->format('Y-m-d-H-i-s').'-'.Str::random(8).'.zip';
        $path = $this->path($filename);
        $stream = fopen($file->getRealPath(), 'rb');

        if ($stream === false) {
            throw new RuntimeException('Yedek dosyası açılamadı.');
        }

        try {
            if (! Storage::disk('recovery_backups')->put($path, $stream, ['visibility' => 'private'])) {
                throw new RuntimeException('Arşiv özel buluta yüklenemedi.');
            }
        } finally {
            fclose($stream);
        }

        return $filename;
    }

    public function validateArchive(string $localPath): void
    {
        $zip = new ZipArchive;
        if ($zip->open($localPath, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException('Geçerli bir ZIP arşivi değil.');
        }

        try {
            $zip->setPassword((string) config('backup.backup.password'));
            $hasEnv = false;
            $hasDatabase = false;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $name = $stat['name'] ?? '';

                if (str_contains($name, "\0") || str_contains($name, '\\') || str_contains($name, ':')
                    || str_starts_with($name, '/') || preg_match('/(^|\/)(\.|\.\.)(\/|$)/', $name)) {
                    throw new RuntimeException('Arşiv güvenli olmayan dosya yolu içeriyor.');
                }

                if (str_ends_with($name, '/')) {
                    continue;
                }

                if (($stat['encryption_method'] ?? ZipArchive::EM_NONE) !== ZipArchive::EM_AES_256
                    || $zip->getFromIndex($i, 1) === false) {
                    throw new RuntimeException('Arşiv AES-256 ile şifrelenmemiş veya parola yanlış.');
                }

                $hasEnv = $hasEnv || $name === '.env';
                $hasDatabase = $hasDatabase || (bool) preg_match('#\Adb-dumps/[^/]+\.(sql|archive)\z#', $name);
            }

            if (! $hasEnv || ! $hasDatabase) {
                throw new RuntimeException('Arşiv tam kurtarma yedeği değil (.env veya veritabanı dökümü eksik).');
            }
        } finally {
            $zip->close();
        }
    }
}
