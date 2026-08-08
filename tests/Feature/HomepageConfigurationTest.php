<?php

namespace Tests\Feature;

use App\Models\Admin\Media\Media;
use App\Models\Admin\User\Role;
use App\Models\Admin\User\User;
use App\Models\Site\SiteLanguage;
use App\Models\Site\SiteHomepageSection;
use App\Models\Site\SiteHomepageSectionItem;
use App\Services\Site\HomepageConfigurationService;
use App\Services\Site\HomepageSectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
            ->assertSee('--home-analysis-tab-after-text:#ffffff', false)
            ->assertSee('--home-hero-after-text:#ffffff', false)
            ->assertSee('assets/site/home/css/home.css?v=', false)
            ->assertSee('data-stat-symbols="true"', false)
            ->assertSee('data-stat-symbol-mode="idle"', false)
            ->assertSee('data-home-mode="analysis"', false)
            ->assertSee('İstatistiksel Analiz')
            ->assertSee('İstatistiksel Danışma')
            ->assertSee('data-home-mode-tab="consultation"', false)
            ->assertSee('Müşteri Memnuniyeti')
            ->assertSee('data-surface="tint"', false)
            ->assertSee('--home-feature-columns: 3', false)
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
        Storage::fake('public');

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
            ->assertSee('name="settings[analysis_tab_after_text_color]"', false)
            ->assertSee('name="settings[hero_after_text_color]"', false)
            ->assertSee('name="settings[tooltip_1_title_color]"', false)
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
                'background_brightness' => 80,
                'background_overlay_opacity' => 45,
                'background_position' => 'top',
                'analysis_tab_after_text_color' => '#112233',
                'hero_after_text_color' => '#223344',
                'consultation_hero_before_text_color' => '#445566',
                'tooltip_1_title_color' => '#334455',
            ]),
            'translations' => [],
            'background_image' => UploadedFile::fake()->image('homepage-background.jpg', 1200, 800),
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
        $this->assertSame(80, $service->current()->fresh()->settings['background_brightness']);
        $this->assertSame(45, $service->current()->fresh()->settings['background_overlay_opacity']);
        $this->assertSame('top', $service->current()->fresh()->settings['background_position']);
        $this->assertSame('#112233', $service->current()->fresh()->settings['analysis_tab_after_text_color']);
        $this->assertSame('#223344', $service->current()->fresh()->settings['hero_after_text_color']);
        $this->assertSame(
            '#223344',
            $service->resolved('tr')['modes']['analysis']['styles']['--home-hero-after-text']
        );
        $this->assertSame(
            '#445566',
            $service->resolved('tr')['modes']['consultation']['styles']['--home-hero-before-text']
        );
        $this->assertSame('#334455', $service->resolved('tr')['tooltips'][0]['title_color']);

        $backgroundMedia = Media::query()->findOrFail(
            $service->current()->fresh()->settings['background_media_id']
        );

        $this->assertSame('image/webp', $backgroundMedia->mime_type);
        Storage::disk('public')->assertExists($backgroundMedia->path);
        $this->assertStringEndsWith('.webp', $backgroundMedia->path);
        $this->assertSame(
            'Danışmanlık sekmesi güncellendi',
            $service->resolved('tr')['modes']['consultation']['hero_title']
        );

        $this->get('/')
            ->assertOk()
            ->assertSee('class="home-background-loading"', false)
            ->assertSee(
                '<link rel="preload" as="image" href="'.$backgroundMedia->url().'" fetchpriority="high">',
                false
            )
            ->assertSee('data-home-background-url="'.$backgroundMedia->url().'"', false)
            ->assertSee('data-stat-symbol-mode="moving"', false)
            ->assertSee('Analiz Merkezi')
            ->assertSee('Uzman Danışmanlık')
            ->assertSee('Danışmanlık sekmesi güncellendi')
            ->assertSee('/danismanlik', false)
            ->assertSee($backgroundMedia->url(), false)
            ->assertSee('--home-background-brightness:80%', false)
            ->assertSee('--home-background-overlay-opacity:0.45', false)
            ->assertSee('--home-background-position:top', false)
            ->assertSee('--home-analysis-tab-after-text:#112233', false)
            ->assertSee('--home-hero-after-text:#223344', false)
            ->assertSee('--home-tooltip-text: #334455', false);
    }

    public function test_superadmin_can_manage_multilingual_homepage_sections_and_cards(): void
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

        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => 'superadmin',
        ]);
        $user = User::query()->create([
            'name' => 'Homepage Sections Admin',
            'email' => 'homepage-sections@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $user->roles()->attach($role);

        $this->actingAs($user)
            ->get(route('admin.site.homepage-sections.index'))
            ->assertOk()
            ->assertSee('Ana Sayfa Bölümleri')
            ->assertSee('Müşteri Memnuniyeti')
            ->assertSee('data-confirm-delete="section"', false)
            ->assertSee('data-homepage-item-sortable', false);

        $this->actingAs($user)
            ->post(route('admin.site.homepage-sections.store'), [
                'eyebrow' => 'Çalışma İlkelerimiz',
                'title' => 'Her adımda yanınızdayız.',
                'description' => 'Projeyi birlikte ve görünür biçimde ilerletiriz.',
                'settings' => [
                    'columns' => 4,
                    'alignment' => 'center',
                    'surface' => 'dark',
                    'accent_color' => '#12ABCD',
                ],
                'is_active' => 1,
                'translations' => [
                    'en' => [
                        'eyebrow' => 'How we work',
                        'title' => 'We are with you at every step.',
                        'description' => 'We move the project forward together.',
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $section = SiteHomepageSection::query()
            ->where('title', 'Her adımda yanınızdayız.')
            ->firstOrFail();

        $this->assertSame(4, $section->settings['columns']);
        $this->assertSame('#12abcd', $section->settings['accent_color']);
        $this->assertDatabaseHas('site_homepage_section_translations', [
            'site_homepage_section_id' => $section->id,
            'locale' => 'en',
            'title' => 'We are with you at every step.',
        ]);

        $this->actingAs($user)
            ->post(route('admin.site.homepage-sections.items.store', $section), [
                'title' => 'Hızlı Teslim',
                'description' => 'Net takvim ve düzenli bilgilendirme ile ilerleriz.',
                'icon' => 'clock',
                'link_label' => 'Süreci İncele',
                'link_url' => '/projeler',
                'is_active' => 1,
                'translations' => [
                    'en' => [
                        'title' => 'On-time delivery',
                        'description' => 'We work with a clear timeline.',
                        'link_label' => 'Explore the process',
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $item = $section->items()->where('title', 'Hızlı Teslim')->firstOrFail();
        $this->assertDatabaseHas('site_homepage_section_item_translations', [
            'site_homepage_section_item_id' => $item->id,
            'locale' => 'en',
            'title' => 'On-time delivery',
        ]);

        $resolved = app(HomepageSectionService::class)->resolved('en');
        $resolvedSection = collect($resolved)->firstWhere('id', $section->id);

        $this->assertSame('We are with you at every step.', $resolvedSection['title']);
        $this->assertSame('On-time delivery', $resolvedSection['items'][0]['title']);
        $this->assertSame('/projeler', $resolvedSection['items'][0]['link_url']);
        $this->assertSame('dark', $resolvedSection['surface']);
    }

    public function test_homepage_section_manager_rejects_unsafe_links_and_keeps_reordering_scoped(): void
    {
        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => 'superadmin',
        ]);
        $user = User::query()->create([
            'name' => 'Homepage Security Admin',
            'email' => 'homepage-security@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $user->roles()->attach($role);

        $sections = SiteHomepageSection::query()->orderBy('id')->get();
        $firstSection = $sections->firstOrFail();
        $secondSection = SiteHomepageSection::query()->create([
            'type' => 'features',
            'title' => 'İkinci bölüm',
            'settings' => [
                'columns' => 3,
                'alignment' => 'left',
                'surface' => 'light',
                'accent_color' => '#123456',
            ],
            'is_active' => true,
            'sort_order' => 2,
        ]);
        $foreignItem = $secondSection->items()->create([
            'title' => 'Başka bölüm kartı',
            'description' => 'Bu kart diğer bölüme aittir.',
            'icon' => 'shield',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->from(route('admin.site.homepage-sections.index'))
            ->post(route('admin.site.homepage-sections.items.store', $firstSection), [
                'title' => 'Güvensiz kart',
                'description' => 'Bu kayıt oluşturulmamalıdır.',
                'icon' => 'sparkles',
                'link_label' => 'Tıkla',
                'link_url' => 'javascript:alert(1)',
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.site.homepage-sections.index'))
            ->assertSessionHasErrors('link_url');

        $this->assertDatabaseMissing('site_homepage_section_items', ['title' => 'Güvensiz kart']);

        $firstItem = $firstSection->items()->firstOrFail();

        $this->actingAs($user)
            ->patchJson(route('admin.site.homepage-sections.items.reorder', $firstSection), [
                'ids' => [$firstItem->id, $foreignItem->id],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ids');

        $this->assertSame(1, $foreignItem->fresh()->sort_order);
    }
}
