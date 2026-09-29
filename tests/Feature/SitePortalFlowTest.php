<?php

namespace Tests\Feature;

use App\Models\Admin\BlogPost\BlogPost;
use App\Models\Admin\Category;
use App\Models\Admin\Gallery\Gallery;
use App\Models\Admin\Media\Media;
use App\Models\Admin\Project\Project;
use App\Models\Admin\User\Role;
use App\Models\Admin\User\User;
use App\Models\Appointment\Appointment;
use App\Models\ContactMessage;
use App\Models\Member;
use App\Models\Review\ServiceReview;
use App\Services\Project\MemberProjectWorkflowService;
use App\Services\Review\ServiceReviewAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SitePortalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_site_layout_uses_probablue_branding_and_persistent_theme_control(): void
    {
        $this->get(route('site.blog.index'))
            ->assertOk()
            ->assertSee('class="probablue-brand probablue-brand--shell"', false)
            ->assertSee('PROBABLUE')
            ->assertSee('İstatistiksel Analiz ve Danışmanlık')
            ->assertSee('data-site-theme-toggle', false)
            ->assertSee('probablue-site-theme', false)
            ->assertSee('<title>Blog | PROBABLUE</title>', false)
            ->assertSee('assets/site/images/favicon.svg', false)
            ->assertSee('assets/site/images/favicon-32x32.png', false)
            ->assertSee('apple-touch-icon.png', false)
            ->assertDontSee('Laravel')
            ->assertDontSee('data-kt-theme-mode="light"', false);
    }

    public function test_contact_recipient_select_only_shows_non_admin_users_without_exposing_emails(): void
    {
        $providerRole = Role::query()->create([
            'name' => 'Provider',
            'slug' => 'provider',
        ]);
        $adminRole = Role::query()->create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]);
        $superAdminRole = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => 'superadmin',
        ]);
        $recipient = User::query()->create([
            'name' => 'Analiz Uzmanı',
            'email' => 'hidden-recipient@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $admin = User::query()->create([
            'name' => 'İletişim Test Admini',
            'email' => 'hidden-admin@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $superAdmin = User::query()->create([
            'name' => 'İletişim Test Süper Admini',
            'email' => 'hidden-superadmin@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $recipient->roles()->attach($providerRole);
        $admin->roles()->attach($adminRole);
        $superAdmin->roles()->attach($superAdminRole);

        $this->get(route('site.contact-messages.create'))
            ->assertOk()
            ->assertSee('Analiz Uzmanı')
            ->assertSee('class="kt-input w-full', false)
            ->assertSee('data-contact-message-field', false)
            ->assertDontSee('hidden-recipient@example.test')
            ->assertDontSee('İletişim Test Admini')
            ->assertDontSee('İletişim Test Süper Admini')
            ->assertDontSee('hidden-admin@example.test')
            ->assertDontSee('hidden-superadmin@example.test');
    }

    public function test_contact_form_does_not_expose_internal_system_notes_to_guests_or_members(): void
    {
        $member = Member::query()->create([
            'name' => 'Test',
            'surname' => 'Üye',
            'email' => 'contact-member@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);

        foreach ([false, true] as $signedIn) {
            if ($signedIn) {
                $this->actingAs($member, 'member');
            }

            $this->get(route('site.contact-messages.create'))
                ->assertOk()
                ->assertSee('Öncelik Rehberi')
                ->assertDontSee('Sistem Notları')
                ->assertDontSee('admin panel')
                ->assertDontSee('Süper admin tüm mesajları görebilir')
                ->assertDontSee('bu sayfa buna hazır');
        }
    }

    public function test_contact_message_cannot_be_sent_to_admin_or_super_admin(): void
    {
        $adminRole = Role::query()->create(['name' => 'Admin', 'slug' => 'admin']);
        $superAdminRole = Role::query()->create(['name' => 'Super Admin', 'slug' => 'superadmin']);

        $admin = User::query()->create([
            'name' => 'Engellenen Admin',
            'email' => 'blocked-admin@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $superAdmin = User::query()->create([
            'name' => 'Engellenen Süper Admin',
            'email' => 'blocked-superadmin@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $admin->roles()->attach($adminRole);
        $superAdmin->roles()->attach($superAdminRole);

        foreach ([$admin, $superAdmin] as $blockedRecipient) {
            $this->from(route('site.contact-messages.create'))
                ->post(route('site.contact-messages.store'), [
                    'recipient_user_id' => $blockedRecipient->id,
                    'name' => 'Misafir',
                    'surname' => 'Kullanıcı',
                    'contact_channels' => [ContactMessage::CONTACT_CHANNEL_EMAIL],
                    'email' => 'guest@example.test',
                    'subject' => 'İletişim talebi',
                    'priority' => ContactMessage::PRIORITY_NORMAL,
                    'message' => 'Bu mesaj yönetici hesaplarına gönderilmemelidir.',
                ])
                ->assertRedirect(route('site.contact-messages.create'))
                ->assertSessionHasErrors('recipient_user_id');
        }

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_public_blog_can_be_searched_filtered_and_opened(): void
    {
        $category = Category::query()->create([
            'name' => 'Rehberler',
            'slug' => 'rehberler',
            'is_active' => true,
        ]);
        $visiblePost = BlogPost::query()->create([
            'title' => 'Dijital proje rehberi',
            'slug' => 'dijital-proje-rehberi',
            'excerpt' => 'Doğru proje planlaması için kısa rehber.',
            'content' => '<p>Proje planlamasının temel adımları.</p>',
            'is_published' => true,
            'published_at' => now()->subHour(),
        ]);
        $visiblePost->categories()->attach($category);

        BlogPost::query()->create([
            'title' => 'Gizli taslak',
            'slug' => 'gizli-taslak',
            'content' => '<p>Yayınlanmamalı.</p>',
            'is_published' => false,
        ]);

        $this->get(route('site.blog.index', ['q' => 'proje', 'category' => 'rehberler']))
            ->assertOk()
            ->assertSee('Dijital proje rehberi')
            ->assertDontSee('Gizli taslak');

        $this->get(route('site.blog.index', ['q' => 'proje', 'fragment' => 1]))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonFragment(['total' => 1]);

        $this->get(route('site.blog.show', $visiblePost->slug))
            ->assertOk()
            ->assertSee('Proje planlamasının temel adımları.', false);

        $this->get(route('site.blog.show', 'gizli-taslak'))->assertNotFound();
    }

    public function test_only_public_galleries_with_images_are_visible(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('galleries/cover.jpg', 'image-content');

        $media = Media::query()->create([
            'uuid' => (string) Str::uuid(),
            'disk' => 'public',
            'path' => 'galleries/cover.jpg',
            'original_name' => 'cover.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 13,
            'title' => 'Galeri kapağı',
        ]);
        $publicGallery = Gallery::query()->create([
            'name' => 'Yayınlanan çalışmalar',
            'slug' => 'yayinlanan-calismalar',
            'is_public' => true,
            'published_at' => now()->subMinute(),
        ]);
        $publicGallery->items()->create(['media_id' => $media->id, 'sort_order' => 1]);

        $privateGallery = Gallery::query()->create([
            'name' => 'İç galeri',
            'slug' => 'ic-galeri',
            'is_public' => false,
        ]);
        $privateGallery->items()->create(['media_id' => $media->id, 'sort_order' => 1]);

        $this->get(route('site.galleries.index'))
            ->assertOk()
            ->assertSee('Yayınlanan çalışmalar')
            ->assertDontSee('İç galeri');

        $this->get(route('site.galleries.show', $publicGallery->slug))
            ->assertOk()
            ->assertSee('data-gallery-dialog', false)
            ->assertSee('site-lightbox__viewport', false)
            ->assertSee('data-gallery-close', false)
            ->assertSee('data-gallery-prev', false)
            ->assertSee('data-gallery-next', false);

        $this->get(route('site.galleries.show', $privateGallery->slug))->assertNotFound();
    }

    public function test_completed_appointment_creates_one_member_project(): void
    {
        [$member, $provider] = $this->actors();
        $appointment = Appointment::query()->create([
            'provider_id' => $provider->id,
            'member_id' => $member->id,
            'start_at' => now()->subHour(),
            'end_at' => now()->subMinutes(30),
            'blocks' => 1,
            'status' => Appointment::STATUS_COMPLETED,
        ]);

        $workflow = app(MemberProjectWorkflowService::class);
        $first = $workflow->ensureForCompletedAppointment($appointment);
        $second = $workflow->ensureForCompletedAppointment($appointment);

        $this->assertNotNull($first);
        $this->assertSame($first->id, $second?->id);
        $this->assertSame($member->id, $first->member_id);
        $this->assertSame(Project::STATUS_APPOINTMENT_DONE, $first->status);
        $this->assertDatabaseCount('projects', 1);
    }

    public function test_member_can_upload_private_project_files_but_cannot_open_another_members_project(): void
    {
        Storage::fake('local');
        [$member] = $this->actors();
        $otherMember = Member::query()->create([
            'name' => 'Başka',
            'surname' => 'Üye',
            'email' => 'other-member@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $project = Project::query()->create([
            'member_id' => $member->id,
            'title' => 'Portal projesi',
            'slug' => 'portal-projesi',
            'content' => 'Dosya paylaşım projesi.',
            'status' => Project::STATUS_APPOINTMENT_DONE,
        ]);

        $this->actingAs($member, 'member')
            ->post(route('member.projects.files.store', $project), [
                'files' => [UploadedFile::fake()->create('brief.pdf', 64, 'application/pdf')],
                'note' => 'Güncel brief dosyası.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $file = $project->files()->firstOrFail();
        Storage::disk('local')->assertExists($file->path);
        $this->assertSame('brief.pdf', $file->original_name);
        $this->assertSame($member->id, $file->member_id);

        $this->actingAs($otherMember, 'member')
            ->get(route('member.projects.show', $project))
            ->assertNotFound();
    }

    public function test_delivered_project_receives_one_project_review(): void
    {
        [$member, $provider] = $this->actors();
        $appointment = Appointment::query()->create([
            'provider_id' => $provider->id,
            'member_id' => $member->id,
            'start_at' => now()->subDays(2),
            'end_at' => now()->subDays(2)->addHour(),
            'blocks' => 1,
            'status' => Appointment::STATUS_COMPLETED,
        ]);
        $project = Project::query()->create([
            'member_id' => $member->id,
            'appointment_id' => $appointment->id,
            'title' => 'Teslim edilen proje',
            'slug' => 'teslim-edilen-proje',
            'status' => Project::STATUS_DELIVERED,
        ]);

        $service = app(ServiceReviewAssignmentService::class);
        $first = $service->assignForProject($project);
        $second = $service->assignForProject($project);

        $this->assertSame($first?->id, $second?->id);
        $this->assertSame(ServiceReview::SERVICE_PROJECT, $first?->service_type);
        $this->assertSame($provider->id, $first?->provider_user_id);
        $this->assertDatabaseCount('service_reviews', 2);
    }

    public function test_additional_analysis_requires_delivered_report_and_keeps_attachment_private(): void
    {
        Storage::fake('local');
        [$member] = $this->actors();
        $otherMember = Member::query()->create([
            'name' => 'Başka',
            'surname' => 'Üye',
            'email' => 'additional-analysis-other@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $project = Project::query()->create([
            'member_id' => $member->id,
            'title' => 'Raporlu proje',
            'slug' => 'raporlu-proje',
            'status' => Project::STATUS_DELIVERED,
        ]);

        $this->actingAs($member, 'member')
            ->post(route('member.projects.analysis-requests.store', $project), ['message' => 'Yeni karşılaştırma istiyorum.'])
            ->assertStatus(422);

        $reportPath = UploadedFile::fake()->create('rapor.pdf', 64, 'application/pdf')->store('project-files/reports', 'local');
        $project->files()->create([
            'member_id' => null,
            'disk' => 'local',
            'path' => $reportPath,
            'original_name' => 'rapor.pdf',
            'mime_type' => 'application/pdf',
            'size' => 65536,
            'note' => 'Analiz raporu',
        ]);

        $this->actingAs($member, 'member')
            ->get(route('member.projects.show', $project))
            ->assertOk()
            ->assertSee('Aklınızda yeni bir soru mu var?')
            ->assertSee('Raporu indir');

        $this->actingAs($member, 'member')
            ->get(route('member.projects.index'))
            ->assertOk()
            ->assertSee('Rapor ve ek analiz');

        $this->actingAs($member, 'member')
            ->post(route('member.projects.analysis-requests.store', $project), [
                'message' => 'Yaş gruplarını ayrıca karşılaştırabilir miyiz?',
                'documents' => [
                    UploadedFile::fake()->create('ek-veri.csv', 12, 'text/csv'),
                    UploadedFile::fake()->create('ek-tablo.pdf', 24, 'application/pdf'),
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $analysisRequest = $project->analysisRequests()->firstOrFail();
        $this->assertSame('pending', $analysisRequest->status);
        $this->assertSame($member->id, $analysisRequest->member_id);
        $this->assertCount(2, $analysisRequest->files);
        $this->assertEqualsCanonicalizing(['ek-veri.csv', 'ek-tablo.pdf'], $analysisRequest->files->pluck('original_name')->all());
        foreach ($analysisRequest->files as $requestFile) {
            Storage::disk('local')->assertExists($requestFile->path);
        }

        $this->actingAs($member, 'member')
            ->get(route('member.projects.show', $project))
            ->assertOk()
            ->assertSee('ek-veri.csv')
            ->assertSee('ek-tablo.pdf');

        $this->actingAs($otherMember, 'member')
            ->post(route('member.projects.analysis-requests.store', $project), ['message' => 'Başkasının raporunu istiyorum.'])
            ->assertNotFound();
        $this->actingAs($otherMember, 'member')
            ->get(route('member.projects.files.download', [$project, $analysisRequest->files->first()]))
            ->assertNotFound();
    }

    public function test_provider_dashboard_shows_only_own_analysis_requests_and_all_their_files(): void
    {
        Storage::fake('local');
        [$member, $provider] = $this->actors();
        [, $otherProvider] = $this->actors();
        $providerRole = Role::query()->create(['name' => 'Uzman', 'slug' => 'provider']);
        $provider->roles()->attach($providerRole);
        $otherProvider->roles()->attach($providerRole);

        $ownAppointment = Appointment::query()->create([
            'provider_id' => $provider->id,
            'member_id' => $member->id,
            'start_at' => now()->subDay(),
            'end_at' => now()->subDay()->addHour(),
            'blocks' => 1,
            'status' => Appointment::STATUS_COMPLETED,
        ]);
        $otherAppointment = Appointment::query()->create([
            'provider_id' => $otherProvider->id,
            'member_id' => $member->id,
            'start_at' => now()->subDays(2),
            'end_at' => now()->subDays(2)->addHour(),
            'blocks' => 1,
            'status' => Appointment::STATUS_COMPLETED,
        ]);
        $ownProject = Project::query()->create([
            'member_id' => $member->id,
            'appointment_id' => $ownAppointment->id,
            'title' => 'Uzmanın projesi',
            'slug' => 'uzmanin-projesi',
            'status' => Project::STATUS_DELIVERED,
        ]);
        $otherProject = Project::query()->create([
            'member_id' => $member->id,
            'appointment_id' => $otherAppointment->id,
            'title' => 'Başka uzmanın projesi',
            'slug' => 'baska-uzmanin-projesi',
            'status' => Project::STATUS_DELIVERED,
        ]);
        $ownRequest = $ownProject->analysisRequests()->create(['member_id' => $member->id, 'message' => 'Kendi analiz talebi', 'status' => 'pending']);
        $otherRequest = $otherProject->analysisRequests()->create(['member_id' => $member->id, 'message' => 'Gizli analiz talebi', 'status' => 'pending']);

        foreach (['veri.csv', 'tablo.pdf'] as $name) {
            $path = UploadedFile::fake()->create($name, 8)->store('project-files/test', 'local');
            $file = $ownProject->files()->create(['member_id' => $member->id, 'disk' => 'local', 'path' => $path, 'original_name' => $name, 'size' => 8192]);
            $ownRequest->files()->attach($file->id);
        }
        $otherPath = UploadedFile::fake()->create('gizli.pdf', 8)->store('project-files/test', 'local');
        $otherFile = $otherProject->files()->create(['member_id' => $member->id, 'disk' => 'local', 'path' => $otherPath, 'original_name' => 'gizli.pdf', 'size' => 8192]);
        $otherRequest->files()->attach($otherFile->id);

        $this->actingAs($provider)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.analysis-requests.index'));
        $this->actingAs($provider)
            ->get(route('admin.analysis-requests.index'))
            ->assertOk()
            ->assertSee('Kendi analiz talebi')
            ->assertSee('veri.csv')
            ->assertSee('tablo.pdf')
            ->assertDontSee('Gizli analiz talebi')
            ->assertDontSee('gizli.pdf');
        $this->actingAs($provider)
            ->get(route('admin.analysis-requests.files.download', [$ownRequest, $ownRequest->files()->first()]))
            ->assertOk();
        $this->actingAs($provider)
            ->get(route('admin.analysis-requests.files.download', [$otherRequest, $otherFile]))
            ->assertNotFound();
    }

    public function test_member_and_assigned_expert_exchange_reports_messages_and_files_without_admin_approval(): void
    {
        Storage::fake('local');
        [$member, $provider] = $this->actors();
        [, $otherProvider] = $this->actors();
        $providerRole = Role::query()->create(['name' => 'Uzman', 'slug' => 'provider']);
        $adminRole = Role::query()->create(['name' => 'Yönetici', 'slug' => 'admin']);
        $provider->roles()->attach($providerRole);
        $otherProvider->roles()->attach($providerRole);
        $admin = User::query()->create(['name' => 'Yönetici', 'email' => 'oversight@example.test', 'password' => 'password', 'is_active' => true]);
        $admin->roles()->attach($adminRole);

        $appointment = Appointment::query()->create([
            'provider_id' => $provider->id, 'member_id' => $member->id,
            'start_at' => now()->subDay(), 'end_at' => now()->subDay()->addHour(),
            'blocks' => 1, 'status' => Appointment::STATUS_COMPLETED,
        ]);
        $project = Project::query()->create([
            'appointment_id' => $appointment->id, 'member_id' => $member->id,
            'title' => 'Karşılıklı analiz', 'slug' => 'karsilikli-analiz',
            'status' => Project::STATUS_APPOINTMENT_DONE,
        ]);

        $this->actingAs($otherProvider)->get(route('admin.analysis-requests.projects.show', $project))->assertNotFound();
        $this->actingAs($otherProvider)->post(route('admin.analysis-requests.projects.reports.store', $project), [
            'report' => UploadedFile::fake()->create('yabanci.pdf', 10, 'application/pdf'),
        ])->assertNotFound();

        $this->actingAs($provider)->patch(route('admin.analysis-requests.projects.status.update', $project), ['status' => Project::STATUS_DEV_PENDING])->assertRedirect();
        $this->actingAs($provider)->patch(route('admin.analysis-requests.projects.status.update', $project), ['status' => Project::STATUS_DEV_IN_PROGRESS])->assertRedirect();
        $this->assertSame(Project::STATUS_DEV_IN_PROGRESS, $project->fresh()->status);
        $this->assertDatabaseHas('project_workflow_events', ['project_id' => $project->id, 'event_type' => 'project_status_changed', 'body' => 'Analiz']);
        $this->actingAs($admin)->patch(route('admin.analysis-requests.projects.status.update', $project), ['status' => Project::STATUS_APPROVED])->assertForbidden();

        $this->actingAs($provider)->post(route('admin.analysis-requests.projects.reports.store', $project), [
            'report' => UploadedFile::fake()->create('analiz-raporu.pdf', 50, 'application/pdf'),
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertSame(Project::STATUS_DELIVERED, $project->fresh()->status);
        $this->assertDatabaseHas('project_workflow_events', ['project_id' => $project->id, 'event_type' => 'report_delivered', 'actor_id' => $provider->id]);

        $this->actingAs($member, 'member')->get(route('member.projects.show', $project))
            ->assertOk()->assertSee('Süreç geçmişi')->assertSee('analiz-raporu.pdf');
        $this->actingAs($member, 'member')->post(route('member.projects.analysis-requests.store', $project), [
            'message' => 'Yaş gruplarını ayrıca karşılaştırabilir miyiz?',
            'documents' => [UploadedFile::fake()->create('veriler.csv', 10, 'text/csv')],
        ])->assertRedirect();
        $analysisRequest = $project->analysisRequests()->firstOrFail();
        $this->assertDatabaseHas('admin_notifications', ['user_id' => $provider->id, 'title' => 'Üye ek analiz istedi']);

        $this->actingAs($provider)->get(route('admin.analysis-requests.projects.show', $project))
            ->assertOk()->assertSee('Yaş gruplarını ayrıca')->assertSee('veriler.csv');
        $this->actingAs($provider)->post(route('admin.analysis-requests.reply', $analysisRequest), [
            'message' => 'Karşılaştırmalı tabloyu hazırladım.',
            'documents' => [UploadedFile::fake()->create('ek-sonuc.pdf', 30, 'application/pdf')],
            'complete' => '1',
        ])->assertRedirect();
        $this->assertSame('completed', $analysisRequest->fresh()->status);

        $this->actingAs($member, 'member')->get(route('member.projects.index'))
            ->assertOk()->assertSee('yeni bildirim');
        $this->actingAs($member, 'member')->get(route('member.projects.show', $project))
            ->assertOk()->assertSee('Karşılaştırmalı tabloyu hazırladım.')->assertSee('ek-sonuc.pdf');
        $this->actingAs($member, 'member')->post(route('member.projects.analysis-requests.reply', [$project, $analysisRequest]), [
            'message' => 'Teşekkürler, bir ayrıntı daha soracağım.',
        ])->assertRedirect();
        $this->assertSame('pending', $analysisRequest->fresh()->status);

        $this->actingAs($admin)->get(route('admin.analysis-requests.projects.show', $project))
            ->assertOk()->assertSee('Karşılaştırmalı tabloyu hazırladım.')->assertSee('Teşekkürler, bir ayrıntı daha soracağım.');
        $this->actingAs($admin)->post(route('admin.analysis-requests.reply', $analysisRequest), [
            'message' => 'Yönetici yanıtı',
        ])->assertForbidden();
    }

    public function test_member_can_update_profile_and_sensitive_changes_require_current_password(): void
    {
        [$member] = $this->actors();

        $this->actingAs($member, 'member')
            ->put(route('member.account.update'), [
                'name' => 'Güncel',
                'surname' => 'Üye',
                'email' => $member->email,
                'phone' => '05550000000',
            ])
            ->assertRedirect(route('member.account.show'));

        $this->assertSame('Güncel', $member->fresh()->name);

        $this->actingAs($member->fresh(), 'member')
            ->put(route('member.account.update'), [
                'name' => 'Güncel',
                'surname' => 'Üye',
                'email' => 'changed-member@example.test',
                'phone' => '05550000000',
            ])
            ->assertSessionHasErrors('current_password');
    }

    private function actors(): array
    {
        $provider = User::query()->create([
            'name' => 'Portal Test Yetkilisi',
            'email' => 'provider-'.uniqid().'@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $member = Member::query()->create([
            'name' => 'Portal',
            'surname' => 'Üye',
            'email' => 'member-'.uniqid().'@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);

        return [$member, $provider];
    }
}
