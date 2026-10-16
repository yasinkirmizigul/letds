<?php

namespace App\Console\Commands;

use App\Support\Recovery\RecoveryArchives;
use App\Support\Recovery\RecoveryConfiguration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Throwable;

class RecoveryRestoreDatabaseCommand extends Command
{
    protected $signature = 'recovery:restore-db {filename : Buluttaki ZIP dosyasının adı}';

    protected $description = 'Yalnızca boş veritabanına şifreli kurtarma yedeğini yükle';

    public function handle(RecoveryArchives $archives, RecoveryConfiguration $configuration): int
    {
        try {
            $configuration->ensureReady();
            $connection = config('backup.backup.source.databases.0');
            $tables = DB::connection($connection)->getSchemaBuilder()->getTableListing();
            if ($tables !== []) {
                $this->error('Hedef veritabanı boş değil. Canlı veritabanına geri yükleme reddedildi.');

                return self::FAILURE;
            }

            $archive = $archives->path((string) $this->argument('filename'));
            if (! $this->confirm("{$archive} boş {$connection} veritabanına yüklensin mi?", false)) {
                return self::FAILURE;
            }

            $result = Artisan::call('backup:restore', [
                '--disk' => 'recovery_backups',
                '--backup' => $archive,
                '--connection' => $connection,
                '--no-interaction' => true,
            ]);
            $this->output->write(Artisan::output());

            return $result === self::SUCCESS ? self::SUCCESS : self::FAILURE;
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
