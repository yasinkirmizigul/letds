<?php

namespace App\Console\Commands;

use App\Models\Admin\BlogPost\BlogPost;
use App\Models\Admin\Category;
use App\Models\Admin\Media\Media;
use App\Support\Audit\AuditWriter;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TrashPurgeCommand extends Command
{
    protected $signature = 'trash:purge
        {--days= : Kaç günden eski silinmiş kayıtlar purgelensin (env TRASH_PURGE_DAYS fallback)}
        {--dry-run : Silmeden sadece sayıları göster}';

    protected $description = 'Trash’teki (soft-deleted) eski kayıtları kalıcı olarak siler';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? env('TRASH_PURGE_DAYS', 30));
        if ($days < 1) $days = 30;

        $cutoff = Carbon::now()->subDays($days);
        $dry = (bool) $this->option('dry-run');

        // 🔎 Aday sayıları (silmeden önce)
        $catCount = Category::onlyTrashed()->where('deleted_at', '<', $cutoff)->count();
        $blogCount = BlogPost::onlyTrashed()->where('deleted_at', '<', $cutoff)->count();
        $mediaCount = Media::onlyTrashed()->where('deleted_at', '<', $cutoff)->count();

        // ✅ PURGE ÖNCESİ AUDIT LOG (SYSTEM / CLI)
        AuditWriter::system('trash.purge', [
            'days' => $days,
            'cutoff' => $cutoff->toDateTimeString(),
            'dry_run' => $dry,
            'candidates' => [
                'categories' => $catCount,
                'blogs' => $blogCount,
                'media' => $mediaCount,
            ],
        ]);

        $this->info("Purge cutoff: deleted_at < {$cutoff->toDateTimeString()} (days={$days})");
        if ($dry) {
            $this->warn("DRY RUN — silme yapılmayacak");
            $this->line("Categories: {$catCount}, Blogs: {$blogCount}, Media: {$mediaCount}");
            return self::SUCCESS;
        }

        // ===== CATEGORY (bağımlılık kontrollü) =====
        $catDone = 0;
        $catSkipped = 0;

        Category::onlyTrashed()
            ->where('deleted_at', '<', $cutoff)
            ->orderBy('id')
            ->chunkById(200, function ($rows) use (&$catDone, &$catSkipped) {
                foreach ($rows as $cat) {
                    // child category var mı? (trashed dahil)
                    $hasChild = Category::withTrashed()
                        ->where('parent_id', $cat->id)
                        ->exists();
                    if ($hasChild) { $catSkipped++; continue; }

                    // blog bağlı mı? (pivot)
                    $hasBlog = DB::table('categorizables')
                        ->where('category_id', $cat->id)
                        ->where('categorizable_type', BlogPost::class)
                        ->exists();
                    if ($hasBlog) { $catSkipped++; continue; }

                    $cat->forceDelete();
                    $catDone++;
                }
            });

        // ===== BLOG =====
        $blogDone = 0;
        BlogPost::onlyTrashed()
            ->where('deleted_at', '<', $cutoff)
            ->orderBy('id')
            ->chunkById(200, function ($rows) use (&$blogDone) {
                foreach ($rows as $p) {
                    $p->forceDelete();
                    $blogDone++;
                }
            });

        // ===== MEDIA =====
        $mediaDone = 0;
        Media::onlyTrashed()
            ->where('deleted_at', '<', $cutoff)
            ->orderBy('id')
            ->chunkById(200, function ($rows) use (&$mediaDone) {
                foreach ($rows as $m) {
                    $m->forceDelete();
                    $mediaDone++;
                }
            });

        $this->info("Done. Categories: {$catDone} (skipped {$catSkipped}), Blogs: {$blogDone}, Media: {$mediaDone}");
        AuditWriter::system('trash.purge.done', [
            'days' => $days,
            'cutoff' => $cutoff->toDateTimeString(),
            'result' => [
                'categories' => ['purged' => $catDone, 'skipped' => $catSkipped],
                'blogs' => ['purged' => $blogDone],
                'media' => ['purged' => $mediaDone],
            ],
        ]);

        return self::SUCCESS;
    }
}
