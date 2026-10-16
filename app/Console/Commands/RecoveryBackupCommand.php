<?php

namespace App\Console\Commands;

use App\Support\Recovery\RecoveryConfiguration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RecoveryBackupCommand extends Command
{
    protected $signature = 'recovery:backup';

    protected $description = 'Şifreli tam yedeği özel buluta oluştur ve eski yedekleri temizle';

    public function handle(RecoveryConfiguration $configuration): int
    {
        if (! $configuration->isReady()) {
            foreach ($configuration->problems() as $problem) {
                $this->error($problem);
            }

            return self::FAILURE;
        }

        $result = Artisan::call('backup:run', [
            '--only-to-disk' => 'recovery_backups',
            '--disable-notifications' => true,
            '--isolated' => true,
        ]);

        $this->output->write(Artisan::output());

        if ($result !== self::SUCCESS) {
            return self::FAILURE;
        }

        $result = Artisan::call('backup:clean', ['--disable-notifications' => true]);
        $this->output->write(Artisan::output());

        return $result === self::SUCCESS ? self::SUCCESS : self::FAILURE;
    }
}
