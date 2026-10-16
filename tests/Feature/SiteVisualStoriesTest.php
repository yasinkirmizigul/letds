<?php

namespace Tests\Feature;

use App\Models\Admin\Media\Media;
use App\Models\Admin\User\Permission;
use App\Models\Admin\User\Role;
use App\Models\Admin\User\User;
use App\Models\Site\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteVisualStoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_set_global_light_and_dark_theme_colors(): void
    {
        $role = Role::query()->create(['name' => 'Super Admin', 'slug' => 'superadmin']);
        $user = User::query()->create([
            'name' => 'Tema Yöneticisi', 'email' => 'theme-admin@example.test',
            'password' => 'password', 'is_active' => true,
        ]);
        $user->roles()->attach($role);
        $settings = SiteSetting::current();
        $colors = [];
        foreach (SiteSetting::THEME_COLOR_FIELDS as $theme => $fields) {
            foreach (array_keys($fields) as $field) {
                $colors[$theme][$field] = $settings->themePaletteColor('probablue', $theme, $field);
            }
        }
        $colors['light']['primary'] = '#1267AB';
        $colors['dark']['primary'] = '#68B4FF';
        $colors['dark']['paper'] = '#102D40';

        $this->actingAs($user)->get(route('admin.site.settings.edit'))
            ->assertOk()
            ->assertSee('Genel Açık / Koyu Tema Renkleri')
            ->assertSee('name="site_theme_colors[dark][paper]"', false);

        $this->put(route('admin.site.settings.update'), [
            'site_palette' => 'probablue',
            'site_theme_colors' => $colors,
        ])->assertRedirect();

        $this->assertSame('#1267AB', $settings->fresh()->themeColor('light', 'primary'));
        $this->assertSame('#68B4FF', $settings->fresh()->themeColor('dark', 'primary'));
        $this->get(route('site.services.index'))
            ->assertOk()
            ->assertSee('--site-palette-primary:#1267AB', false)
            ->assertSee('--site-palette-primary-rgb:18, 103, 171', false)
            ->assertSee('--site-palette-dark-primary:#68B4FF', false)
            ->assertSee('--site-palette-dark-paper:#102D40', false);

        $colors['dark']['paper'] = 'not-a-color';
        $this->put(route('admin.site.settings.update'), [
            'site_palette' => 'probablue', 'site_theme_colors' => $colors,
        ])->assertSessionHasErrors('site_theme_colors.dark.paper');
        $this->assertSame('#102D40', $settings->fresh()->themeColor('dark', 'paper'));
    }

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
        $lightColors = ['#A97200', '#315D88', '#147D83', '#7857A2'];
        $this->assertSame(SiteSetting::SERVICES_SYMBOL_DEFAULTS, SiteSetting::current()->servicesSymbolColors());
        $this->assertSame(SiteSetting::SERVICES_SYMBOL_LIGHT_DEFAULTS, SiteSetting::current()->servicesSymbolColors('light'));
        $this->assertSame('#f7fbfd', SiteSetting::current()->servicesHeroBackground('light'));
        $this->assertSame('#1a3341', SiteSetting::current()->servicesHeroBackground('dark'));
        $this->get(route('site.home'))
            ->assertOk()
            ->assertSee('--home-before-bg:#fafcff', false)
            ->assertSee('--home-after-bg:#0058d4', false);

        $this->actingAs($user)
            ->get(route('admin.site.services.index'))
            ->assertOk()
            ->assertSee('Neler Sunuyoruz? / Hareketli P')
            ->assertSee('name="services_symbol_colors[0]"', false)
            ->assertSee('name="services_symbol_light_colors[0]"', false)
            ->assertSee('name="services_hero_light_background"', false)
            ->assertSee('name="services_hero_dark_background"', false)
            ->assertSee('data-services-reset', false);

        $this->get(route('admin.site.settings.edit'))
            ->assertOk()
            ->assertDontSee('Neler Sunuyoruz? / Hareketli P');

        $this->actingAs($user)
            ->put(route('admin.site.services.symbol-colors.update'), [
                'services_symbol_colors' => $colors,
                'services_symbol_light_colors' => $lightColors,
                'services_hero_light_background' => '#F6FAFF',
                'services_hero_dark_background' => '#193744',
            ])
            ->assertRedirect(route('admin.site.services.index'));

        $this->assertDatabaseHas('audit_logs', ['action' => 'site.services.symbol-colors.update']);
        $this->assertSame($colors, SiteSetting::current()->fresh()->servicesSymbolColors());
        $this->assertSame($lightColors, SiteSetting::current()->fresh()->servicesSymbolColors('light'));
        $this->assertSame('#F6FAFF', SiteSetting::current()->fresh()->servicesHeroBackground('light'));
        $this->assertSame('#193744', SiteSetting::current()->fresh()->servicesHeroBackground('dark'));
        $this->get(route('site.services.index'))
            ->assertOk()
            ->assertSee('--symbol-dark-1: #12549A', false)
            ->assertSee('--symbol-dark-4: #B5DEF2', false)
            ->assertSee('--symbol-light-1: #A97200', false)
            ->assertSee('--symbol-light-4: #7857A2', false)
            ->assertSee('--services-hero-light-bg: #F6FAFF', false)
            ->assertSee('--services-hero-dark-bg: #193744', false)
            ->assertSee('data-logo-src=', false);

        $this->actingAs($user)
            ->put(route('admin.site.settings.update'), [
                'site_name' => 'Probablue',
                'services_symbol_colors' => ['#000000', '#000000', '#000000', '#000000'],
            ])
            ->assertRedirect(route('admin.site.settings.edit'));
        $this->assertSame($colors, SiteSetting::current()->fresh()->servicesSymbolColors());

        $this->actingAs($user)
            ->put(route('admin.site.services.symbol-colors.update'), ['services_symbol_colors' => ['#000000', 'bad', '#FFFFFF', '#123456']])
            ->assertSessionHasErrors('services_symbol_colors.1');
        $this->assertSame($colors, SiteSetting::current()->fresh()->servicesSymbolColors());

        $this->actingAs($user)
            ->put(route('admin.site.services.symbol-colors.update'), [
                'services_symbol_colors' => $colors,
                'services_symbol_light_colors' => ['#A97200', 'bad', '#147D83', '#7857A2'],
                'services_hero_light_background' => 'not-a-color',
            ])
            ->assertSessionHasErrors(['services_symbol_light_colors.1', 'services_hero_light_background']);
        $this->assertSame($lightColors, SiteSetting::current()->fresh()->servicesSymbolColors('light'));
    }

    public function test_services_manager_can_save_symbol_colors_without_site_settings_permission(): void
    {
        $role = Role::query()->create(['name' => 'Admin', 'slug' => 'admin']);
        foreach (['site_homepage.view', 'site_homepage.update'] as $slug) {
            $role->permissions()->attach(Permission::query()->create(['name' => $slug, 'slug' => $slug]));
        }
        $user = User::query()->create([
            'name' => 'Hizmet Editörü', 'email' => 'services-editor@example.test',
            'password' => 'password', 'is_active' => true,
        ]);
        $user->roles()->attach($role);
        $colors = ['#12549A', '#509CD3', '#8FC8E8', '#B5DEF2'];

        $this->actingAs($user)
            ->get(route('admin.site.services.index'))
            ->assertOk()
            ->assertSee('Neler Sunuyoruz? / Hareketli P');

        $this->put(route('admin.site.services.symbol-colors.update'), ['services_symbol_colors' => $colors])
            ->assertRedirect(route('admin.site.services.index'));
        $this->assertSame($colors, SiteSetting::current()->fresh()->servicesSymbolColors());
        $this->get(route('admin.site.settings.edit'))->assertForbidden();
    }

    public function test_services_theme_defaults_can_be_restored_without_javascript(): void
    {
        $role = Role::query()->create(['name' => 'Super Admin', 'slug' => 'superadmin']);
        $user = User::query()->create([
            'name' => 'Site Yönetimi', 'email' => 'services-reset-admin@example.test',
            'password' => 'password', 'is_active' => true,
        ]);
        $user->roles()->attach($role);

        $settings = SiteSetting::current();
        $settings->update([
            'services_symbol_colors' => ['#111111', '#222222', '#333333', '#444444'],
            'services_symbol_light_colors' => ['#555555', '#666666', '#777777', '#888888'],
            'services_hero_light_background' => '#EEEEEE',
            'services_hero_dark_background' => '#111111',
        ]);

        $this->actingAs($user)
            ->put(route('admin.site.services.symbol-colors.update'), [
                'restore_theme' => 'dark',
                'services_symbol_colors' => ['invalid'],
            ])
            ->assertRedirect(route('admin.site.services.index'));

        $settings->refresh();
        $this->assertSame(SiteSetting::SERVICES_SYMBOL_DEFAULTS, $settings->servicesSymbolColors('dark'));
        $this->assertSame(SiteSetting::SERVICES_HERO_DARK_BACKGROUND_DEFAULT, $settings->servicesHeroBackground('dark'));
        $this->assertSame(['#555555', '#666666', '#777777', '#888888'], $settings->servicesSymbolColors('light'));
        $this->assertSame('#EEEEEE', $settings->servicesHeroBackground('light'));

        $this->put(route('admin.site.services.symbol-colors.update'), ['restore_theme' => 'light'])
            ->assertRedirect(route('admin.site.services.index'));

        $settings->refresh();
        $this->assertSame(SiteSetting::SERVICES_SYMBOL_LIGHT_DEFAULTS, $settings->servicesSymbolColors('light'));
        $this->assertSame(SiteSetting::SERVICES_HERO_LIGHT_BACKGROUND_DEFAULT, $settings->servicesHeroBackground('light'));
    }
}
