<?php

namespace Tests\Feature;

use App\Models\Admin\User\Permission;
use App\Models\Admin\User\Role;
use App\Models\Admin\User\User;
use App\Models\Admin\AuditLog\AuditLog;
use App\Models\ContactMessage;
use App\Support\Admin\AdminRoleProfile;
use Database\Seeders\AdminSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRoleProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_has_full_panel_permissions_while_global_menu_management_stays_with_superadmin(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(SuperAdminSeeder::class);
        $this->seed(AdminSeeder::class);

        $admin = Role::query()->where('slug', 'admin')->firstOrFail();
        $superAdmin = Role::query()->where('slug', 'superadmin')->firstOrFail();
        $adminPermissions = $admin->permissions()->pluck('slug');

        $this->assertGreaterThan(900, $superAdmin->priority);
        $this->assertSame(900, $admin->priority);
        $this->assertContains('blog.view', $adminPermissions);
        $this->assertContains('projects.update', $adminPermissions);
        $this->assertContains('products.state_change', $adminPermissions);
        $this->assertContains('appointments.update', $adminPermissions);
        $this->assertContains('messages.view', $adminPermissions);
        $this->assertContains('site_homepage.update', $adminPermissions);
        $this->assertContains('site_settings.update', $adminPermissions);
        $this->assertContains('trash.restore', $adminPermissions);

        $this->assertContains('users.view', $adminPermissions);
        $this->assertContains('roles.update', $adminPermissions);
        $this->assertContains('permissions.delete', $adminPermissions);
        $this->assertContains('audit-logs.view', $adminPermissions);
        $this->assertContains('audit-logs.clear', $adminPermissions);
        $this->assertContains('ecommerce_webhooks.update', $adminPermissions);
        $this->assertContains('trash.force_delete', $adminPermissions);
        $this->assertContains('blog.force_delete', $adminPermissions);
        $this->assertFalse(AdminRoleProfile::allows('developer.console'));
        $this->assertFalse(AdminRoleProfile::allows('future_technical_module.view'));

        $this->assertEqualsCanonicalizing(
            Permission::query()->pluck('id')->all(),
            $superAdmin->permissions()->pluck('permissions.id')->all()
        );

        $adminUser = User::query()
            ->whereHas('roles', fn ($query) => $query->where('slug', 'admin'))
            ->firstOrFail();

        $this->actingAs($adminUser)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Operasyon')
            ->assertSee('İçerik ve Vitrin')
            ->assertSee('Satış ve Katalog')
            ->assertSee('Site Yapılandırması')
            ->assertSee('Silinenler')
            ->assertSee('Kullanıcı ve Yetki')
            ->assertSee('Sistem Kayıtları')
            ->assertDontSee('Menü Yönetimi');

        $this->get(route('admin.site.homepage.edit'))->assertOk();
        $this->get(route('admin.messages.index'))->assertOk();
        $this->get(route('admin.appointments.calendar'))->assertOk();
        $this->get(route('admin.users.index'))->assertOk();
        $this->get(route('admin.roles.index'))->assertOk();
        $this->get(route('admin.permissions.index'))->assertOk();
        $this->get(route('admin.audit-logs.index'))->assertOk();
        $this->get(route('admin.ecommerce.webhooks.index'))->assertOk();
        $this->get(route('admin.menu-visibility.edit'))->assertForbidden();

        $superAdminUser = User::query()
            ->whereHas('roles', fn ($query) => $query->where('slug', 'superadmin'))
            ->firstOrFail();

        $this->actingAs($superAdminUser)
            ->get(route('admin.menu-visibility.edit'))
            ->assertOk()
            ->assertSee('Menü Yönetimi');
    }

    public function test_role_hierarchy_protects_admin_and_superadmin_accounts(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(SuperAdminSeeder::class);
        $this->seed(AdminSeeder::class);

        $adminRole = Role::query()->where('slug', 'admin')->firstOrFail();
        $superAdminRole = Role::query()->where('slug', 'superadmin')->firstOrFail();
        $editorRole = Role::query()->create(['name' => 'Editor', 'slug' => 'editor', 'priority' => 100]);
        $admin = User::query()->whereHas('roles', fn ($query) => $query->where('slug', 'admin'))->firstOrFail();
        $superAdmin = User::query()->whereHas('roles', fn ($query) => $query->where('slug', 'superadmin'))->firstOrFail();
        $otherAdmin = User::query()->create([
            'name' => 'Other Admin',
            'email' => 'other-admin@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $editor = User::query()->create([
            'name' => 'Editor User',
            'email' => 'editor@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $otherAdmin->roles()->attach($adminRole);
        $editor->roles()->attach($editorRole);

        $this->actingAs($admin);
        $this->get(route('admin.users.edit', $editor))->assertOk();
        $this->get(route('admin.users.profile', $editor))->assertOk();
        $this->get(route('admin.users.edit', $otherAdmin))->assertForbidden();
        $this->get(route('admin.users.edit', $superAdmin))->assertForbidden();
        $this->get(route('admin.roles.edit', $adminRole))->assertForbidden();
        $this->get(route('admin.roles.edit', $superAdminRole))->assertForbidden();

        $this->actingAs($superAdmin);
        $this->get(route('admin.users.edit', $admin))->assertOk();
        $this->get(route('admin.roles.edit', $adminRole))->assertOk();
        $this->get(route('admin.users.edit', $superAdmin))->assertForbidden();
    }

    public function test_admin_and_superadmin_can_clear_audit_logs(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(SuperAdminSeeder::class);
        $this->seed(AdminSeeder::class);

        $admin = User::query()->whereHas('roles', fn ($query) => $query->where('slug', 'admin'))->firstOrFail();
        $superAdmin = User::query()->whereHas('roles', fn ($query) => $query->where('slug', 'superadmin'))->firstOrFail();
        $adminOldLog = AuditLog::query()->create(['action' => 'old.admin.log', 'status' => 200]);

        $this->actingAs($admin)
            ->delete(route('admin.audit-logs.clear'))
            ->assertRedirect(route('admin.audit-logs.index'));

        $this->assertDatabaseMissing('audit_logs', ['id' => $adminOldLog->id]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'audit-logs.clear',
        ]);

        $superAdminOldLog = AuditLog::query()->create(['action' => 'old.superadmin.log', 'status' => 200]);

        $this->actingAs($superAdmin)
            ->delete(route('admin.audit-logs.clear'))
            ->assertRedirect(route('admin.audit-logs.index'));

        $this->assertDatabaseMissing('audit_logs', ['id' => $superAdminOldLog->id]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $superAdmin->id,
            'action' => 'audit-logs.clear',
        ]);
    }

    public function test_admin_has_global_operational_visibility_without_developer_permissions(): void
    {
        $adminRole = Role::query()->create(['name' => 'Admin', 'slug' => 'admin', 'priority' => 900]);
        $providerRole = Role::query()->create(['name' => 'Provider', 'slug' => 'provider', 'priority' => 100]);
        $admin = User::query()->create([
            'name' => 'General Admin',
            'email' => 'general-admin@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $provider = User::query()->create([
            'name' => 'Provider',
            'email' => 'provider@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $otherProvider = User::query()->create([
            'name' => 'Other Provider',
            'email' => 'other-provider@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);

        $admin->roles()->attach($adminRole);
        $provider->roles()->attach($providerRole);
        $otherProvider->roles()->attach($providerRole);

        $message = ContactMessage::query()->create([
            'recipient_user_id' => $provider->id,
            'recipient_name' => $provider->name,
            'sender_type' => ContactMessage::SENDER_TYPE_GUEST,
            'sender_name' => 'Test',
            'sender_surname' => 'Visitor',
            'sender_email' => 'visitor@example.test',
            'preferred_channels' => [ContactMessage::CONTACT_CHANNEL_EMAIL],
            'subject' => 'Provider inbox message',
            'priority' => ContactMessage::PRIORITY_NORMAL,
            'message' => 'Operational visibility test.',
        ]);

        $this->assertTrue($admin->hasGlobalOperationalScope());
        $this->assertFalse($provider->hasGlobalOperationalScope());
        $this->assertTrue($message->isVisibleToUser($admin));
        $this->assertTrue($message->isVisibleToUser($provider));
        $this->assertFalse($message->isVisibleToUser($otherProvider));
        $this->assertSame([$message->id], ContactMessage::query()->visibleToUser($admin)->pluck('id')->all());
    }
}
