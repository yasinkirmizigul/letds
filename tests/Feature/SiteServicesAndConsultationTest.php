<?php

namespace Tests\Feature;

use App\Models\Admin\User\Permission;
use App\Models\Admin\User\Role;
use App\Models\Admin\User\User;
use App\Models\Appointment\AppointmentMeetingMethod;
use App\Models\Member;
use App\Models\Site\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SiteServicesAndConsultationTest extends TestCase
{
    use RefreshDatabase;

    public function test_services_page_renders_managed_services_and_process_without_detail_links(): void
    {
        $this->get(route('site.services.index'))
            ->assertOk()
            ->assertSee('Araştırmanızın her aşamasında güvenilir istatistik desteği.')
            ->assertSee('Araştırma Tasarımı')
            ->assertSee('Yapay Zeka ve Veri Bilimi')
            ->assertSee('Ön Görüşme')
            ->assertSee('Revizyon Desteği')
            ->assertSee(route('member.appointments.index'), false)
            ->assertDontSee('Detaylı İncele');
    }

    public function test_consultation_requires_login_and_uses_member_data_in_three_step_preview(): void
    {
        $this->get(route('member.appointments.index'))
            ->assertRedirect(route('member.login'));

        $providerRole = Role::query()->create(['name' => 'Provider', 'slug' => 'provider']);
        $provider = User::query()->create([
            'name' => 'Analiz Uzmanı',
            'email' => 'consultation-provider@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $provider->roles()->attach($providerRole);

        $member = Member::query()->create([
            'name' => 'Yasemin',
            'surname' => 'Araştırmacı',
            'email' => 'yasemin@example.test',
            'phone' => '05550000000',
            'institution' => 'Ankara Üniversitesi',
            'password' => 'password',
            'is_active' => true,
        ]);

        $this->actingAs($member, 'member')
            ->get(route('member.appointments.index'))
            ->assertOk()
            ->assertSee('data-appointment-step="1"', false)
            ->assertSee('data-appointment-step="2"', false)
            ->assertSee('data-appointment-step="3"', false)
            ->assertSee('Yasemin Araştırmacı')
            ->assertSee('yasemin@example.test')
            ->assertSee('05550000000')
            ->assertSee('Ankara Üniversitesi')
            ->assertSee('Google Meet')
            ->assertSee('Telefon Görüşmesi')
            ->assertSee('appointmentMemberNote', false)
            ->assertSee('appointmentPreviewMeetingMethod', false)
            ->assertSee('Analiz Uzmanı')
            ->assertSee('appointmentPreviewProvider', false)
            ->assertSee('Randevuyu Onayla');
    }

    public function test_only_admin_and_superadmin_roles_can_manage_meeting_methods(): void
    {
        $permission = Permission::query()->create([
            'name' => 'Randevu Ayarlarını Güncelle',
            'slug' => 'appointments.update',
        ]);
        $adminRole = Role::query()->create(['name' => 'Admin', 'slug' => 'admin']);
        $providerRole = Role::query()->create(['name' => 'Provider', 'slug' => 'provider']);
        $adminRole->permissions()->attach($permission);
        $providerRole->permissions()->attach($permission);
        Cache::forever('rbac:version', 'meeting-methods-'.uniqid('', true));

        $admin = User::query()->create([
            'name' => 'Randevu Yöneticisi',
            'email' => 'appointment-admin@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $admin->roles()->attach($adminRole);

        $provider = User::query()->create([
            'name' => 'Randevu Uzmanı',
            'email' => 'appointment-provider@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $provider->roles()->attach($providerRole);

        $this->actingAs($admin)
            ->postJson(route('admin.appointments.meeting-methods.store'), [
                'name' => 'Microsoft Teams',
                'description' => 'Çevrim içi ekip görüşmesi',
                'is_active' => true,
                'sort_order' => 40,
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Görüşme yöntemi eklendi.');

        $this->assertDatabaseHas('appointment_meeting_methods', [
            'name' => 'Microsoft Teams',
            'is_active' => true,
        ]);

        $this->actingAs($provider)
            ->postJson(route('admin.appointments.meeting-methods.store'), [
                'name' => 'Yetkisiz Yöntem',
                'is_active' => true,
                'sort_order' => 50,
            ])
            ->assertForbidden();

        $this->assertFalse(AppointmentMeetingMethod::query()->where('name', 'Yetkisiz Yöntem')->exists());
    }

    public function test_superadmin_can_open_services_manager_and_site_palette_is_applied(): void
    {
        $role = Role::query()->create(['name' => 'Super Admin', 'slug' => 'superadmin']);
        $user = User::query()->create([
            'name' => 'Services Admin',
            'email' => 'services-admin@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $user->roles()->attach($role);

        $this->actingAs($user)
            ->get(route('admin.site.services.index'))
            ->assertOk()
            ->assertSee('Hizmetler Yönetimi')
            ->assertSee('Hizmet kartları')
            ->assertSee('Süreç adımları')
            ->assertSee('Araştırma Tasarımı');

        SiteSetting::current()->update(['site_palette' => 'probablue']);

        $this->get(route('site.services.index'))
            ->assertOk()
            ->assertSee('data-site-palette="probablue"', false)
            ->assertSee('--site-palette-primary:#087cf0', false)
            ->assertSee('--site-palette-dark-background:#0d2038', false);
    }
}
