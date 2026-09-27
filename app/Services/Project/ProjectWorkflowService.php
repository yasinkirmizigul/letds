<?php

namespace App\Services\Project;

use App\Jobs\SendProjectStageWhatsAppJob;
use App\Models\Admin\AdminNotification;
use App\Models\Admin\Project\Project;
use App\Models\Admin\Project\ProjectWorkflowEvent;
use App\Services\Admin\AdminNotificationService;

class ProjectWorkflowService
{
    public function __construct(
        private readonly AdminNotificationService $notifications,
        private readonly ProjectStageWhatsAppService $whatsapp,
    ) {}

    public function record(Project $project, string $type, string $actorType, ?int $actorId = null, ?string $body = null, array $data = [], ?int $analysisRequestId = null): ProjectWorkflowEvent
    {
        $event = $project->workflowEvents()->create([
            'analysis_request_id' => $analysisRequestId,
            'event_type' => $type,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'body' => $body,
            'data' => $data ?: null,
            'member_seen_at' => $actorType === 'member' ? now() : null,
            'provider_seen_at' => $actorType === 'provider' ? now() : null,
        ]);

        $project->touch();

        if ($actorType !== 'provider') {
            $project->loadMissing('appointment.provider');
            $this->notifications->notifyUser($project->appointment?->provider, [
                'type' => AdminNotification::TYPE_SYSTEM,
                'title' => $event->label(),
                'body' => $project->title,
                'action_label' => 'Süreci aç',
                'action_url' => route('admin.analysis-requests.projects.show', $project),
                'source_type' => ProjectWorkflowEvent::class,
                'source_id' => $event->id,
            ]);
        }

        $project->loadMissing('member');
        if ($this->whatsapp->isConfigured()
            && $this->whatsapp->stageFor($event->setRelation('project', $project))
            && $this->whatsapp->eligiblePhone($project->member, $event)) {
            SendProjectStageWhatsAppJob::dispatch($event->id)->afterCommit();
        }

        return $event;
    }
}
