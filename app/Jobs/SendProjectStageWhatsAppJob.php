<?php

namespace App\Jobs;

use App\Models\Admin\Project\ProjectWorkflowEvent;
use App\Services\Project\ProjectStageWhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendProjectStageWhatsAppJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly int $eventId) {}

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(ProjectStageWhatsAppService $whatsapp): void
    {
        if (! $whatsapp->isConfigured()) {
            return;
        }

        $event = ProjectWorkflowEvent::query()->with('project.member')->find($this->eventId);
        if (! $event || $event->whatsapp_stage_accepted_at) {
            return;
        }

        $stage = $whatsapp->stageFor($event);
        $phone = $whatsapp->eligiblePhone($event->project?->member, $event);
        if (! $stage || ! $phone) {
            return;
        }

        $messageId = $whatsapp->sendStage($phone, $stage);
        $event->forceFill([
            'whatsapp_stage_accepted_at' => now(),
            'whatsapp_stage_message_id' => $messageId !== '' ? $messageId : null,
        ])->save();
    }
}
