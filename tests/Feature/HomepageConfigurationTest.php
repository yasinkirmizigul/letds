<?php

namespace Tests\Feature;

use App\Models\Admin\User\Role;
use App\Models\Admin\User\User;
use App\Models\Site\SiteLanguage;
use App\Services\Site\HomepageConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_uses_schema_defaults_and_dynamic_color_variables(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('The combination of great design and diligent app development.')
            ->assertSee('--home-before-bg:#ffffff', false)
            ->assertSee('--home-stat-before:#ec6367', false)
            ->assertSee('data-stat-symbols="true"', false)
            ->assertSee('data-stat-symbol-mode="idle"', false)
            ->assertSee('data-home-mode="analysis"', false)
            ->assertSee('İstatistiksel Analiz')
            ->assertSee('İstatistiksel Danışma')
            ->assertSee('data-home-mode-tab="consultation"', false)
            ->assertSee('VIEW THEMES');

        $this->assertDatabaseCount('site_homepage_configs', 1);
    }

    public function test_configuration_resolves_translations_and_sanitizes_tooltip_html(): void
    {
        SiteLanguage::query()->updateOrCreate(
            ['code' => 'en'],
            [
                'name' => 'English',
                'native_name' => 'English',
                'is_active' => true,
                'is_default' => false,
                'is_rtl' => false,
                'sort_order' => 2,
            ]
        );

        $service = app(HomepageConfigurationService::class);
        $payload = array_replace($service->contentDefaults(), [
            'hero_title' => 'Türkçe ana başlık',
            'cta_url' => '/iletisim',
            'tooltip_1_title' => '<script>alert(1)</script><strong>Güvenli metin</strong>',
            'settings' => array_replace($service->settingDefaults(), [
                'after_background_color' => '#123456',
            ]),
            'translations' => [
                'en' => [
                    'hero_title' => 'English homepage title',
                    'cta_label' => 'CONTACT US',
                ],
            ],
        ]);

        $service->persist($payload);

        $turkish = $service->resolved('tr');
        $english = $service->resolved('en');

        $this->assertSame('Türkçe ana başlık', $turkish['content']['hero_title']);
        $this->assertSame('English homepage title', $english['content']['hero_title']);
        $this->assertSame('CONTACT US', $english['content']['cta_label']);
        $this->assertSame('/iletisim', $english['content']['cta_url']);
        $this->assertSame('#123456', $english['settings']['after_background_color']);
        $this->assertSame('English homepage title', $english['modes']['analysis']['hero_title']);
        $this->assertSame('İstatistiksel Danışma', $english['modes']['consultation']['label']);
        $this->assertStringNotContainsString('<script', $turkish['tooltips'][0]['title']);
        $this->assertStringContainsString('<strong>Güvenli metin</strong>', $turkish['tooltips'][0]['title']);
    }

    public function test_superadmin_can_open_and_update_the_custom_homepage_manager(): void
    {
        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => 'superadmin',
        ]);
        $user = User::query()->create([
            'name' => 'Homepage Test Admin',
            'email' => 'homepage-admin@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $user->roles()->attach($role);

        $service = app(HomepageConfigurationService::class);

        $this->actingAs($user)
            ->get(route('admin.site.homepage.edit'))
            ->assertOk()
            ->assertSee('Ana Sayfa Yönetimi')
            ->assertSee('Ana Sayfa Sekmeleri')
            ->assertSee('İstatistiksel Analiz İçeriği')
            ->assertSee('İstatistiksel Danışma İçeriği')
            ->assertSee('data-homepage-admin-mode-label-key="analysis_tab_label"', false)
            ->assertSee('Sembol çalışma biçimi')
            ->assertSee('Panel Renkleri');

        $payload = array_replace($service->contentDefaults(), [
            'analysis_tab_label' => 'Analiz Merkezi',
            'hero_title' => 'Dashboard üzerinden güncellendi',
            'cta_label' => 'BİZE ULAŞIN',
            'cta_url' => 'https://example.com/contact',
            'consultation_tab_label' => 'Uzman Danışmanlık',
            'consultation_hero_title' => 'Danışmanlık sekmesi güncellendi',
            'consultation_cta_label' => 'DANIŞMANLIK ALIN',
            'consultation_cta_url' => '/danismanlik',
            'settings' => array_replace($service->settingDefaults(), [
                'cursor_symbol_mode' => 'moving',
            ]),
            'translations' => [],
        ]);

        $this->actingAs($user)
            ->put(route('admin.site.homepage.update'), $payload)
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(
            'Dashboard üzerinden güncellendi',
            $service->current()->fresh()->content['hero_title']
        );
        $this->assertSame('moving', $service->current()->fresh()->settings['cursor_symbol_mode']);
        $this->assertSame(
            'Danışmanlık sekmesi güncellendi',
            $service->resolved('tr')['modes']['consultation']['hero_title']
        );

        $this->get('/')
            ->assertOk()
            ->assertSee('data-stat-symbol-mode="moving"', false)
            ->assertSee('Analiz Merkezi')
            ->assertSee('Uzman Danışmanlık')
            ->assertSee('Danışmanlık sekmesi güncellendi')
            ->assertSee('/danismanlik', false);
    }
}
