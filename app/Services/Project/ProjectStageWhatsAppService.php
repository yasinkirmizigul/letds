<?php

namespace App\Services\Project;

use App\Models\Admin\Project\Project;
use App\Models\Admin\Project\ProjectWorkflowEvent;
use App\Models\Member;
use Illuminate\Support\Facades\Http;

class ProjectStageWhatsAppService
{
    public static function normalizePhone(?string $phone): ?string
    {
        $input = preg_replace('/[\s().-]+/', '', trim((string) $phone));
        if (! $input) {
            return null;
        }

        if (str_starts_with($input, '+')) {
            $digits = substr($input, 1);
            return preg_match('/^[1-9]\d{7,14}$/', $digits) ? $digits : null;
        }

        if (! ctype_digit($input)) {
            return null;
        }

        if (preg_match('/^05\d{9}$/', $input)) {
            return '9'.$input;
        }
        if (preg_match('/^5\d{9}$/', $input)) {
            return '90'.$input;
        }
        if (preg_match('/^905\d{9}$/', $input)) {
            return $input;
        }
        if (preg_match('/^00905\d{9}$/', $input)) {
            return substr($input, 2);
        }

        return null;
    }

    public function stageFor(ProjectWorkflowEvent $event): ?string
    {
        $status = match ($event->event_type) {
            'project_created' => $event->data['status'] ?? null,
            'project_status_changed' => $event->data['to'] ?? null,
            'report_delivered' => ($event->data['stage_changed'] ?? false) ? Project::STATUS_DELIVERED : null,
            default => null,
        };

        return in_array($status, [
            Project::STATUS_APPOINTMENT_DONE,
            Project::STATUS_DEV_PENDING,
            Project::STATUS_DEV_IN_PROGRESS,
            Project::STATUS_DELIVERED,
            Project::STATUS_APPROVED,
        ], true) ? Project::statusLabel($status) : null;
    }

    public function eligiblePhone(?Member $member, ?ProjectWorkflowEvent $event = null): ?string
    {
        if (! $member || ! $member->is_active || ! $member->whatsapp_stage_opted_in_at) {
            return null;
        }
        if ($event && $member->whatsapp_stage_opted_in_at->greaterThan($event->created_at)) {
            return null;
        }

        $phone = self::normalizePhone($member->phone);

        return $phone !== null && $phone === $member->whatsapp_stage_opted_in_phone ? $phone : null;
    }

    public function isConfigured(): bool
    {
        $settings = config('services.whatsapp_stage', []);

        return (bool) ($settings['enabled'] ?? false)
            && preg_match('/^v\d+\.\d+$/', (string) ($settings['graph_version'] ?? '')) === 1
            && preg_match('/^\d+$/', (string) ($settings['phone_number_id'] ?? '')) === 1
            && filled($settings['access_token'] ?? null)
            && preg_match('/^[a-z0-9_]+$/', (string) ($settings['template_name'] ?? '')) === 1
            && preg_match('/^[a-z]{2}(?:_[A-Z]{2})?$/', (string) ($settings['template_language'] ?? '')) === 1;
    }

    public function sendStage(string $phone, string $stage): string
    {
        $settings = config('services.whatsapp_stage');
        $response = Http::withToken($settings['access_token'])
            ->acceptJson()
            ->timeout(15)
            ->post('https://graph.facebook.com/'.$settings['graph_version'].'/'.$settings['phone_number_id'].'/messages', [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $phone,
                'type' => 'template',
                'template' => [
                    'name' => $settings['template_name'],
                    'language' => ['code' => $settings['template_language']],
                    'components' => [[
                        'type' => 'body',
                        'parameters' => [['type' => 'text', 'text' => $stage]],
                    ]],
                ],
            ]);

        $response->throw();

        return (string) $response->json('messages.0.id');
    }
}
