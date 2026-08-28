<?php

namespace Tests\Feature;

use App\Models\Admin\Media\Media;
use App\Models\Admin\User\Permission;
use App\Models\Admin\User\Role;
use App\Models\Admin\User\User;
use App\Models\Site\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminLoginBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_has_theme_toggle_and_visible_remember_control(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-kt-theme-switch-toggle="true"', false)
            ->assertSee('Koyu Mod')
            ->assertSee('Açık Mod')
            ->assertSee('class="admin-auth-remember"', false)
            ->assertSee('id="admin_login_remember"', false)
            ->assertSee('assets/admin/media/app/favicon.svg', false)
            ->assertSee('assets/admin/media/app/favicon-32x32.png', false)
            ->assertSee('favicon.ico', false)
            ->assertSee('apple-touch-icon.png', false);
    }

    public function test_dashboard_user_menu_has_a_descriptive_theme_control(): void
    {
        $user = $this->createSuperAdmin();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('data-admin-user-menu', false)
            ->assertSee('data-admin-user-theme-control', false)
            ->assertSee('id="admin_user_theme_switch"', false)
            ->assertSee('data-sidebar-tooltip=', false)
            ->assertSee('data-kt-menu-item-toggle="accordion"', false)
            ->assertSee('aria-expanded=', false)
            ->assertSee('Koyu moda geç')
            ->assertSee('Açık moda geç')
            ->assertSee('Hesap bilgilerini yönet')
            ->assertSee('Güvenli çıkış');
    }

    public function test_superadmin_can_upload_and_remove_the_admin_login_logo(): void
    {
        Storage::fake('public');

        $user = $this->createSuperAdmin();

        $this->actingAs($user)
            ->put(route('admin.site.settings.update'), [
                'submit_action' => 'save',
                'site_name' => 'Letds Test',
                'admin_login_logo' => UploadedFile::fake()->image('login-logo.png', 640, 240),
            ])
            ->assertRedirect(route('admin.site.settings.edit'))
            ->assertSessionHas('success');

        $settings = SiteSetting::current()->fresh();
        $logo = Media::query()->findOrFail($settings->admin_login_logo_media_id);

        $this->assertSame('image/webp', $logo->mime_type);
        $this->assertSame('admin/login-branding', dirname($logo->path));
        Storage::disk('public')->assertExists($logo->path);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('src="'.$logo->url().'"', false)
            ->assertSee('alt="Letds Test"', false);

        $this->actingAs($user)
            ->put(route('admin.site.settings.update'), [
                'submit_action' => 'save',
                'site_name' => 'Letds Test',
                'clear_admin_login_logo' => '1',
            ])
            ->assertRedirect(route('admin.site.settings.edit'))
            ->assertSessionHas('success');

        $this->assertNull(SiteSetting::current()->fresh()->admin_login_logo_media_id);
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('src="'.$logo->url().'"', false);
    }

    public function test_regular_admin_cannot_see_or_change_the_admin_login_logo(): void
    {
        Storage::fake('public');

        $role = Role::query()->create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]);
        $permissions = collect(['site_settings.view', 'site_settings.update'])
            ->map(fn (string $slug) => Permission::query()->create([
                'name' => $slug,
                'slug' => $slug,
            ]));
        $role->permissions()->attach($permissions->pluck('id'));

        $user = User::query()->create([
            'name' => 'Regular Admin',
            'email' => 'regular-admin@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $user->roles()->attach($role);

        $this->actingAs($user)
            ->get(route('admin.site.settings.edit'))
            ->assertOk()
            ->assertDontSee('data-admin-login-branding', false);

        $this->actingAs($user)
            ->put(route('admin.site.settings.update'), [
                'submit_action' => 'save',
                'admin_login_logo' => UploadedFile::fake()->image('forbidden-logo.png', 640, 240),
            ])
            ->assertForbidden();

        $this->assertNull(SiteSetting::current()->fresh()->admin_login_logo_media_id);
        $this->assertDatabaseCount('media', 0);
    }

    private function createSuperAdmin(): User
    {
        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => 'superadmin',
        ]);

        $user = User::query()->create([
            'name' => 'Login Branding Admin',
            'email' => 'login-branding@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);

        $user->roles()->attach($role);

        return $user;
    }
}
