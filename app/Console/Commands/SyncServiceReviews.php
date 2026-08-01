<?php

namespace App\Console\Commands;

use App\Services\Review\ServiceReviewAssignmentService;
use Illuminate\Console\Command;

class SyncServiceReviews extends Command
{
    protected $signature = 'service-reviews:sync {--member= : Yalnızca belirtilen üyenin tamamlanan hizmetlerini tara}';

    protected $description = 'Tamamlanan randevu ve siparişler için eksik değerlendirme davetlerini oluşturur';

    public function handle(ServiceReviewAssignmentService $assignmentService): int
    {
        $memberId = $this->option('member');
        $created = $assignmentService->syncCompletedServices(
            filled($memberId) ? (int) $memberId : null
        );

        $this->info("{$created} değerlendirme daveti oluşturuldu.");

        return self::SUCCESS;
    }
}
