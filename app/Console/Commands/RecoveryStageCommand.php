<?php

namespace App\Console\Commands;

use App\Support\Recovery\RecoveryArchives;
use App\Support\Recovery\RecoveryConfiguration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

class RecoveryStageCommand extends Command
{
    protected $signature = 'recovery:stage {filename : Buluttaki ZIP dosyasının adı}';

    protected $description = 'Şifreli yedekten .env ve yüklenen dosyaları güvenli hazırlık klasörüne çıkar';

    public function handle(RecoveryArchives $archives, RecoveryConfiguration $configuration): int
    {
        try {
            $configuration->ensureReady();
            $path = $archives->path((string) $this->argument('filename'));
            $source = Storage::disk('recovery_backups')->readStream($path);
            if ($source === false) {
                throw new RuntimeException('Bulut arşivi okunamadı.');
            }

            $stage = storage_path('app/recovery-staging/'.now()->format('Ymd-His').'-'.Str::random(8));
            if (! mkdir($stage, 0700, true)) {
                throw new RuntimeException('Hazırlık klasörü oluşturulamadı.');
            }

            $localZip = $stage.DIRECTORY_SEPARATOR.'archive.zip';
            $target = fopen($localZip, 'wb');
            if ($target === false) {
                fclose($source);
                throw new RuntimeException('Geçici arşiv dosyası oluşturulamadı.');
            }

            try {
                if (stream_copy_to_stream($source, $target) === false) {
                    throw new RuntimeException('Bulut arşivi tam olarak indirilemedi.');
                }
            } finally {
                fclose($source);
                fclose($target);
            }
            chmod($localZip, 0600);

            $archives->validateArchive($localZip);
            $zip = new ZipArchive;
            if ($zip->open($localZip, ZipArchive::RDONLY) !== true) {
                throw new RuntimeException('İndirilen arşiv açılamadı.');
            }

            try {
                $zip->setPassword((string) config('backup.backup.password'));

                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = $zip->getNameIndex($i);
                    if ($name === false || str_ends_with($name, '/')) {
                        continue;
                    }

                    if ($name !== '.env' && ! in_array($name, ['public/sitemap.xml', 'public/robots.txt', 'public/llms.txt'], true)
                        && ! str_starts_with($name, 'storage/app/public/')
                        && ! str_starts_with($name, 'storage/app/private/')) {
                        continue;
                    }

                    $destination = $stage.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $name);
                    $directory = dirname($destination);
                    if (! is_dir($directory) && ! mkdir($directory, 0700, true)) {
                        throw new RuntimeException('Çıkarma klasörü oluşturulamadı.');
                    }

                    $input = $zip->getStream($name);
                    $output = fopen($destination, 'wb');
                    if ($input === false || $output === false) {
                        if (is_resource($input)) {
                            fclose($input);
                        }
                        if (is_resource($output)) {
                            fclose($output);
                        }
                        throw new RuntimeException('Şifreli dosya çıkarılamadı.');
                    }

                    try {
                        if (stream_copy_to_stream($input, $output) === false) {
                            throw new RuntimeException('Şifreli dosya tam olarak çıkarılamadı.');
                        }
                    } finally {
                        fclose($input);
                        fclose($output);
                    }
                    chmod($destination, 0600);
                }
            } finally {
                $zip->close();
                unlink($localZip);
            }

            $this->info("Dosyalar hazırlandı: {$stage}");
            $this->warn('Mevcut .env veya yüklenen dosyalar otomatik üzerine yazılmadı. Kurtarma kılavuzundaki sırayı izleyin.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
