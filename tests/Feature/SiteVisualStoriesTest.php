<?php

namespace Tests\Feature;

use App\Models\Admin\Media\Media;
use App\Models\Admin\User\Role;
use App\Models\Admin\User\User;
use App\Models\Site\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteVisualStoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_page_shows_active_experts_with_their_dashboard_portraits(): void
    {
        Storage::fake('public');
        $providerRole = Role::query()->create(['name' => 'Uzman', 'slug' => 'provider']);
        $adminRole = Role::query()->create(['name' => 'Yönetici', 'slug' => 'admin']);
        $media = Media::query()->create([
            'uuid' => 'portrait-test', 'disk' => 'public', 'path' => 'uploads/avatars/portrait.png',
            'original_name' => 'portrait.png', 'mime_type' => 'image/png', 'size' => 10,
        ]);
        $expert = User::query()->create([
            'name' => 'Ayşe İstatistik', 'title' => 'Veri Analizi Uzmanı',
            'email' => 'ayse-expert@example.test', 'password' => 'password',
            'is_active' => true, 'avatar_media_id' => $media->id,
            'bio' => 'Araştırma tasarımı ve veri analizi.', 'skills' => ['Regresyon', 'Ölçek geliştirme'],
        ]);
        $expert->roles()->attach($providerRole);
        $secondExpert = User::query()->create([
            'name' => 'Berk Araştırmacı', 'title' => 'Araştırma Tasarımı Uzmanı',
            'email' => 'berk-expert@example.test', 'password' => 'password', 'is_active' => true,
        ]);
        $secondExpert->roles()->attach($providerRole);
        $hidden = User::query()->create([
            'name' => 'Gizli Yönetici', 'email' => 'hidden-admin@example.test',
            'password' => 'password', 'is_active' => true,
        ]);
        $hidden->roles()->attach($adminRole);
        $inactive = User::query()->create([
            'name' => 'Pasif Uzman', 'email' => 'inactive-expert@example.test',
            'password' => 'password', 'is_active' => false,
        ]);
        $inactive->roles()->attach($providerRole);

        $this->get(route('site.pages.show', 'hakkimizda'))
            ->assertOk()
            ->assertSee('data-site-team', false)
            ->assertSee('data-site-team-next', false)
            ->assertSee('Ayşe İstatistik')
            ->assertSee('Berk Araştırmacı')
            ->assertSee('Veri Analizi Uzmanı')
            ->assertSee($media->url(), false)
            ->assertSee('Regresyon')
            ->assertDontSee('Gizli Yönetici')
            ->assertDontSee('Pasif Uzman');
    }

    public function test_dashboard_controls_four_symbol_colors_without_replacing_site_palette(): void
    {
        $role = Role::query()->create(['name' => 'Super Admin', 'slug' => 'superadmin']);
        $user = User::query()->create([
            'name' => 'Site Yönetimi', 'email' => 'site-visual-admin@example.test',
            'password' => 'password', 'is_active' => true,
        ]);
        $user->roles()->attach($role);
        $colors = ['#12549A', '#509CD3', '#8FC8E8', '#B5DEF2'];

        $this->actingAs($user)
            ->get(route('admin.site.settings.edit'))
            ->assertOk()
            ->assertSee('Neler Sunuyoruz? / Hareketli P');

        $this->actingAs($user)
            ->put(route('admin.site.settings.update'), ['services_symbol_colors' => $colors])
            ->assertRedirect(route('admin.site.settings.edit'));

        $this->assertSame($colors, SiteSetting::current()->fresh()->servicesSymbolColors());
        $this->get(route('site.services.index'))
            ->assertOk()
            ->assertSee('--symbol-1: #12549A', false)
            ->assertSee('--symbol-4: #B5DEF2', false)
            ->assertSee('data-logo-src=', false);

        $this->actingAs($user)
            ->put(route('admin.site.settings.update'), ['site_name' => 'Probablue'])
            ->assertRedirect(route('admin.site.settings.edit'));
        $this->assertSame($colors, SiteSetting::current()->fresh()->servicesSymbolColors());

        $this->actingAs($user)
            ->put(route('admin.site.settings.update'), ['services_symbol_colors' => ['#000000', 'bad', '#FFFFFF', '#123456']])
            ->assertSessionHasErrors('services_symbol_colors.1');
        $this->assertSame($colors, SiteSetting::current()->fresh()->servicesSymbolColors());
    }
}
