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
            ->assertSee('İstatistiksel Analiz ve Danışma')
            ->assertSee('data-site-theme-toggle', false)
            ->assertSee('probablue-site-theme', false)
            ->assertSee('<title>Blog | PROBABLUE</title>', false)
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
            ->assertDontSee('hidden-recipient@example.test')
            ->assertDontSee('İletişim Test Admini')
            ->assertDontSee('İletişim Test Süper Admini')
            ->assertDontSee('hidden-admin@example.test')
            ->assertDontSee('hidden-superadmin@example.test');
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
