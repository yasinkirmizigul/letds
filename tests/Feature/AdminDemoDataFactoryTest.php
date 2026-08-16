<?php

namespace Tests\Feature;

use App\Models\Admin\AdminMenuSetting;
use App\Models\Admin\BlogPost\BlogPost;
use App\Models\Admin\Ecommerce\EcommerceOrder;
use App\Models\Admin\Media\Media;
use App\Models\Admin\Product\Product;
use App\Models\Admin\Project\ProjectFile;
use App\Models\Admin\User\Permission;
use App\Models\Admin\User\Role;
use App\Models\Admin\User\User;
use App\Models\Appointment\Appointment;
use App\Models\ContactMessage;
use App\Models\Review\ServiceReview;
use App\Models\Site\PaymentIntegration;
use App\Models\Site\SiteFaq;
use App\Models\Site\SiteHomepageConfig;
use App\Models\Site\SiteNavigationItem;
use App\Models\Site\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminDemoDataFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_super_admin_can_open_or_run_demo_data_factory(): void
    {
        $superAdmin = $this->userWithRole('superadmin', 'Super Admin');
        $admin = $this->userWithRole('admin', 'Admin');

        $this->actingAs($superAdmin)
            ->get(route('admin.demo-data.index'))
            ->assertOk()
            ->assertSee('Örnek Veri Fabrikası')
            ->assertSee(route('admin.demo-data.generate'), false)
            ->assertSee(route('admin.demo-data.reset'), false);

        $this->actingAs($admin)
            ->get(route('admin.demo-data.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.demo-data.generate'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('admin.demo-data.reset'))
            ->assertForbidden();
    }

    public function test_super_admin_can_generate_relational_sample_data_for_main_modules(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $superAdmin = $this->userWithRole('superadmin', 'Super Admin');
        $navigationIds = SiteNavigationItem::query()->orderBy('id')->pluck('id')->all();

        $this->actingAs($superAdmin)
            ->post(route('admin.demo-data.generate'))
            ->assertRedirect(route('admin.demo-data.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('members', 8);
        $this->assertDatabaseCount('blog_posts', 8);
        $this->assertDatabaseCount('products', 8);
        $this->assertDatabaseCount('projects', 6);
        $this->assertDatabaseCount('appointments', 12);
        $this->assertDatabaseCount('contact_messages', 12);
        $this->assertDatabaseCount('ecommerce_orders', 6);
        $this->assertGreaterThanOrEqual(8, SiteFaq::query()->count());
        $this->assertGreaterThanOrEqual(6, ServiceReview::query()->count());
        $this->assertDatabaseCount('home_sliders', 3);
        $this->assertSame($navigationIds, SiteNavigationItem::query()->orderBy('id')->pluck('id')->all());

        $provider = User::query()->where('email', 'like', 'demo.uzman1.%')->firstOrFail();
        $this->assertTrue($provider->hasRole('provider'));
        $this->assertTrue(Hash::check('Demo123!', $provider->password));
        $this->assertTrue($provider->receivedContactMessages()->exists());

        $this->assertTrue(BlogPost::query()->firstOrFail()->categories()->exists());
        $this->assertCount(2, Product::query()->firstOrFail()->variants);
        $this->assertTrue(Appointment::query()->firstOrFail()->slots()->exists());
        $this->assertTrue(ContactMessage::query()->firstOrFail()->recipient()->exists());
        $this->assertTrue(ServiceReview::query()->completed()->firstOrFail()->items()->exists());
        $this->assertTrue(EcommerceOrder::query()->firstOrFail()->items()->exists());

        $media = Media::query()->where('meta->source', 'demo-data-factory')->firstOrFail();
        Storage::disk($media->disk)->assertExists($media->path);
    }

    public function test_reset_removes_operational_data_but_preserves_system_core_and_design_media(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $superAdmin = $this->userWithRole('superadmin', 'Super Admin');
        $admin = $this->userWithRole('admin', 'Admin');
        $provider = $this->userWithRole('provider', 'Uzman');
        $permission = Permission::query()->create([
            'name' => 'Örnek Yetki',
            'slug' => 'example.keep',
        ]);

        Storage::disk('public')->put('branding/protected-logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        $protectedMedia = Media::query()->create([
            'uuid' => (string) Str::uuid(),
            'disk' => 'public',
            'path' => 'branding/protected-logo.svg',
            'original_name' => 'protected-logo.svg',
            'mime_type' => 'image/svg+xml',
            'size' => 48,
            'title' => 'Korunan Logo',
        ]);
        SiteSetting::current()->forceFill(['admin_login_logo_media_id' => $protectedMedia->id])->save();
        $homepageConfig = SiteHomepageConfig::query()->create([
            'key' => 'main',
            'content' => ['hero_title' => 'Korunan ana sayfa'],
            'settings' => ['header_logo_media_id' => $protectedMedia->id],
        ]);
        $payment = PaymentIntegration::query()->create([
            'provider' => 'manual',
            'title' => 'Korunan Ödeme Ayarı',
            'integration_type' => 'manual',
            'environment' => PaymentIntegration::ENV_SANDBOX,
            'is_active' => false,
            'is_default' => false,
        ]);
        AdminMenuSetting::query()->create([
            'id' => AdminMenuSetting::SINGLETON_ID,
            'hidden_items' => ['content.media'],
            'updated_by' => $provider->id,
        ]);

        $this->actingAs($superAdmin)->post(route('admin.demo-data.generate'))->assertRedirect();
        $demoMedia = Media::query()->where('meta->source', 'demo-data-factory')->firstOrFail();
        $demoMediaPath = $demoMedia->path;
        $projectFile = ProjectFile::query()->value('path');

        Storage::disk('public')->assertExists($demoMediaPath);
        Storage::disk('local')->assertExists($projectFile);

        $this->actingAs($superAdmin)
            ->delete(route('admin.demo-data.reset'))
            ->assertRedirect(route('admin.demo-data.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $superAdmin->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseMissing('users', ['id' => $provider->id]);
        $this->assertSame(2, User::query()->count());
        $this->assertDatabaseHas('roles', ['slug' => 'superadmin']);
        $this->assertDatabaseHas('roles', ['slug' => 'admin']);
        $this->assertDatabaseHas('permissions', ['id' => $permission->id]);
        $this->assertDatabaseHas('site_homepage_configs', ['id' => $homepageConfig->id]);
        $this->assertDatabaseHas('payment_integrations', ['id' => $payment->id]);
        $this->assertDatabaseHas('media', ['id' => $protectedMedia->id]);
        $this->assertDatabaseHas('admin_menu_settings', [
            'id' => AdminMenuSetting::SINGLETON_ID,
            'updated_by' => null,
        ]);

        foreach ([
            'members', 'blog_posts', 'products', 'projects', 'appointments',
            'contact_messages', 'ecommerce_orders', 'site_pages', 'site_faqs',
            'site_navigation_items', 'service_reviews', 'admin_notifications',
        ] as $table) {
            $this->assertDatabaseCount($table, 0);
        }

        Storage::disk('public')->assertExists('branding/protected-logo.svg');
        Storage::disk('public')->assertMissing($demoMediaPath);
        Storage::disk('local')->assertMissing($projectFile);
    }

    private function userWithRole(string $slug, string $name): User
    {
        $role = Role::query()->firstOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'priority' => $slug === 'superadmin' ? 1000 : 500]
        );
        $user = User::query()->create([
            'name' => $name,
            'email' => $slug.'-'.Str::lower(Str::random(6)).'@example.test',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $user->roles()->attach($role);

        return $user;
    }
}
