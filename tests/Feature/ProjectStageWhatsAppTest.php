<?php

namespace Tests\Feature;

use App\Jobs\SendProjectStageWhatsAppJob;
use App\Models\Admin\Project\Project;
use App\Models\Admin\Project\ProjectWorkflowEvent;
use App\Models\Member;
use App\Services\Project\ProjectStageWhatsAppService;
use App\Services\Project\ProjectWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProjectStageWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_controls_explicit_stage_only_whatsapp_consent(): void
    {
        $member = $this->member();
        $payload = [
            'name' => $member->name,
            'surname' => $member->surname,
            'email' => $member->email,
            'phone' => '0555 123 45 67',
            'whatsapp_stage_notifications' => '1',
        ];

        $this->actingAs($member, 'member')->get(route('member.account.edit'))
            ->assertOk()->assertSee('Analiz aşaması bildirimlerini WhatsApp üzerinden almak istiyorum.');

        $this->actingAs($member, 'member')->put(route('member.account.update'), $payload)->assertRedirect();
        $this->assertSame('905551234567', $member->fresh()->whatsapp_stage_opted_in_phone);
        $this->assertNotNull($member->fresh()->whatsapp_stage_opted_in_at);

        $this->actingAs($member, 'member')->put(route('member.account.update'), [
            ...$payload,
            'phone' => 'geçersiz',
        ])->assertSessionHasErrors('phone');

        unset($payload['whatsapp_stage_notifications']);
        $this->actingAs($member, 'member')->put(route('member.account.update'), $payload)->assertRedirect();
        $this->assertNull($member->fresh()->whatsapp_stage_opted_in_at);
        $this->assertNull($member->fresh()->whatsapp_stage_opted_in_phone);
    }

    public function test_only_real_stage_events_queue_a_member_whatsapp_notification(): void
    {
        $this->configureWhatsApp();
        Bus::fake([SendProjectStageWhatsAppJob::class]);
        $member = $this->consentingMember();
        $project = $this->project($member);
        $workflow = app(ProjectWorkflowService::class);

        $workflow->record($project, 'member_message', 'member', $member->id, 'Gizli mesaj');
        $workflow->record($project, 'documents_added', 'member', $member->id, data: ['file_ids' => [17]]);
        $workflow->record($project, 'request_created', 'member', $member->id, 'Ek analiz');
        $workflow->record($project, 'report_delivered', 'provider', 1, data: ['stage_changed' => false]);
        Bus::assertNotDispatched(SendProjectStageWhatsAppJob::class);

        $event = $workflow->record($project, 'project_status_changed', 'provider', 1, data: [
            'from' => Project::STATUS_APPOINTMENT_DONE,
            'to' => Project::STATUS_DEV_PENDING,
        ]);
        Bus::assertDispatched(SendProjectStageWhatsAppJob::class, fn ($job) => $job->eventId === $event->id);
    }

    public function test_whatsapp_payload_contains_only_the_stage_and_stops_after_consent_is_revoked(): void
    {
        $this->configureWhatsApp();
        $member = $this->consentingMember();
        $project = $this->project($member);
        $event = $project->workflowEvents()->create([
            'event_type' => 'project_status_changed',
            'actor_type' => 'provider',
            'body' => 'Gizli uzman notu',
            'data' => ['to' => Project::STATUS_DELIVERED, 'file_ids' => [91]],
        ]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.test']]], 200)]);

        $job = new SendProjectStageWhatsAppJob($event->id);
        $job->handle(app(ProjectStageWhatsAppService::class));

        Http::assertSent(function ($request): bool {
            $payload = $request->data();

            return $payload['to'] === '905551234567'
                && $payload['type'] === 'template'
                && $payload['template']['components'][0]['parameters'] === [[
                    'type' => 'text', 'text' => Project::statusLabel(Project::STATUS_DELIVERED),
                ]]
                && ! str_contains(json_encode($payload), 'Gizli uzman notu')
                && ! str_contains(json_encode($payload), 'file_ids');
        });
        $this->assertNotNull($event->fresh()->whatsapp_stage_accepted_at);
        $this->assertSame('wamid.test', $event->fresh()->whatsapp_stage_message_id);

        $member->forceFill(['whatsapp_stage_opted_in_at' => null, 'whatsapp_stage_opted_in_phone' => null])->save();
        $secondEvent = $project->workflowEvents()->create([
            'event_type' => 'project_status_changed',
            'actor_type' => 'provider',
            'data' => ['to' => Project::STATUS_APPROVED],
        ]);
        (new SendProjectStageWhatsAppJob($secondEvent->id))->handle(app(ProjectStageWhatsAppService::class));
        Http::assertSentCount(1);
    }

    public function test_old_events_do_not_become_sendable_after_later_consent(): void
    {
        $this->configureWhatsApp();
        $member = $this->member();
        $project = $this->project($member);
        $event = $project->workflowEvents()->create([
            'event_type' => 'project_status_changed',
            'actor_type' => 'provider',
            'data' => ['to' => Project::STATUS_DEV_PENDING],
        ]);
        $event->forceFill(['created_at' => now()->subMinute()])->save();
        $member->forceFill([
            'whatsapp_stage_opted_in_at' => now(),
            'whatsapp_stage_opted_in_phone' => '905551234567',
        ])->save();
        Http::fake();

        (new SendProjectStageWhatsAppJob($event->id))->handle(app(ProjectStageWhatsAppService::class));

        Http::assertNothingSent();
    }

    private function configureWhatsApp(): void
    {
        config()->set('services.whatsapp_stage', [
            'enabled' => true,
            'graph_version' => 'v23.0',
            'phone_number_id' => '123456',
            'access_token' => 'test-token',
            'template_name' => 'analysis_stage_update',
            'template_language' => 'tr',
        ]);
    }

    private function member(): Member
    {
        return Member::query()->create([
            'name' => 'Test', 'surname' => 'Üye', 'email' => 'whatsapp-member@example.test',
            'phone' => '0555 123 45 67', 'password' => 'password', 'is_active' => true,
        ]);
    }

    private function consentingMember(): Member
    {
        $member = $this->member();
        $member->forceFill([
            'whatsapp_stage_opted_in_at' => now()->subMinute(),
            'whatsapp_stage_opted_in_phone' => '905551234567',
        ])->save();

        return $member;
    }

    private function project(Member $member): Project
    {
        return Project::query()->create([
            'member_id' => $member->id,
            'title' => 'Gizli proje adı',
            'slug' => 'whatsapp-test-project',
            'status' => Project::STATUS_APPOINTMENT_DONE,
        ]);
    }
}
