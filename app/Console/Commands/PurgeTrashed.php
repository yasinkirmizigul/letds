<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use App\Models\Admin\Media\Media;

class PurgeTrashed extends Command
{
    // İstersen seçeneklerle yönet:
    // php artisan purge:trashed --media=30 --dry-run
    protected $signature = 'purge:trashed
        {--media=30 : Medya için silindikten sonra kaç gün bekletilecek}
        {--dry-run : Silmeden sadece kaç kayıt olduğunu yazdırır}';

    protected $description = 'Soft-deleted kayıtları yaşına göre kalıcı siler (force delete).';

    public function handle(): int
    {
        $mediaDays = max(1, (int) $this->option('media'));
        $dryRun = (bool) $this->option('dry-run');

        $cutoff = Carbon::now()->subDays($mediaDays);

        $query = Media::onlyTrashed()->where('deleted_at', '<', $cutoff);

        $count = (int) $query->count();
        $this->info("Media purge aday sayısı: {$count} (>{$mediaDays} gün)");

        if ($dryRun) {
            $this->comment('dry-run aktif, silme yapılmadı.');
            return self::SUCCESS;
        }

        // forceDelete => model booted() içindeki forceDeleted event'i ile disk temizliği de olur
        $deleted = 0;

        $query->orderBy('id')->chunkById(200, function ($items) use (&$deleted) {
            foreach ($items as $m) {
                $m->forceDelete();
                $deleted++;
            }
        });

        $this->info("Media purge tamamlandı. Kalıcı silinen: {$deleted}");
        return self::SUCCESS;
    }
}
