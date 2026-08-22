<?php

namespace Tests\Feature;

use App\Models\Admin\Media\Media;
use App\Models\Admin\User\Role;
use App\Models\Admin\User\User;
use App\Models\Site\SiteHomepageSection;
use App\Models\Site\SiteLanguage;
use App\Models\Site\SiteSetting;
use App\Services\Site\HomepageConfigurationService;
use App\Services\Site\HomepageSectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class HomepageConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_uses_schema_defaults_and_dynamic_color_variables(): void
    {
        $resolved = app(HomepageConfigurationService::class)->resolved('tr');

        $this->assertStringStartsWith(
            asset('assets/site/home/images/home-background-light.svg') . '?v=',
            data_get($resolved, 'backgroundDefaults.light.url')
        );
        $this->assertStringStartsWith(
            asset('assets/site/home/images/home-background-dark.svg') . '?v=',
            data_get($resolved, 'backgroundDefaults.dark.url')
        );

        $this->get('/')
            ->assertOk()
            ->assertSee('The combination of great design and diligent app development.')
            ->assertSee('--home-before-bg:#ffffff', false)
            ->assertSee('--home-before-color-layer:var(--home-before-bg)', false)
            ->assertSee('--home-before-surface-opacity:1', false)
            ->assertSee('--home-after-color-layer:radial-gradient(', false)
            ->assertSee('--home-before-pattern-image:none', false)
            ->assertSee('--home-before-pattern-opacity:0.18', false)
            ->assertSee('--home-stat-before:#ec6367', false)
            ->assertSee('--home-analysis-tab-after-text:#ffffff', false)
            ->assertSee('--home-hero-after-text:#ffffff', false)
            ->assertSee('--home-computer-frame:#072247', false)
            ->assertSee('--home-computer-alert:#0046d6', false)
            ->assertSee('--home-computer-gradient-end:#0060ea', false)
            ->assertSee('data-home-computer-variant="pvt"', false)
            ->assertSee('data-home-computer-variant="pv"', false)
            ->assertDontSee('images/concept-before.svg', false)
            ->assertDontSee('images/concept-after.svg', false)
            ->assertSee('assets/site/home/css/home.css?v=', false)
            ->assertSee('data-stat-symbols="true"', false)
            ->assertSee('data-stat-symbol-mode="idle"', false)
            ->assertSee('data-home-mode="analysis"', false)
            ->assertSee('class="home-hero-pending', false)
            ->assertSee('data-site-theme-toggle', false)
            ->assertSee('home-background-light.svg', false)
            ->assertSee('home-background-dark.svg', false)
            ->assertSee('probablue-site-theme', false)
            ->assertSee('PROBABLUE')
            ->assertSee('İstatistiksel Analiz ve Danışma')
            ->assertDontSee('id="logo-bird"', false)
            ->assertSee('class="home-surface-pattern"', false)
            ->assertSee('İstatistiksel Analiz')
            ->assertSee('İstatistiksel Danışma')
            ->assertSee('data-home-mode-tab="consultation"', false)
            ->assertSee('Müşteri Memnuniyeti')
            ->assertSee('data-surface="tint"', false)
            ->assertSee('--home-feature-columns: 3', false)
            ->assertSee('VIEW THEMES');

        $this->assertDatabaseCount('site_homepage_configs', 1);
    }

    public function test_probablue_palette_recolors_the_custom_homepage_and_mode_payloads(): void
    {
        SiteSetting::current()->update(['site_palette' => 'probablue']);

        $this->get('/')
            ->assertOk()
            ->assertSee('data-site-palette="probablue"', false)
            ->assertSee('--home-before-bg:#fafcff', false)
            ->assertSee('--home-after-bg:#0058d4', false)
            ->assertSee('--home-before-text:#27313d', false)
            ->assertSee('--home-sticky-logo:#0058d4', false)
            ->assertSee('--home-feature-palette-accent:#0058d4', false)
            ->assertSee('--home-feature-palette-bg:#fafcff', false)
            ->assertSee('--home-feature-palette-dark-bg:#0d2038', false)
            ->assertDontSee('--home-after-bg:#ec6367', false);
    }

    public function test_homepage_can_switch_to_probablue_split_layout_without_loading_interactive_compare(): void
    {
        $service = app(HomepageConfigurationService::class);
        $config = $service->current();
        $config->update([
            'settings' => array_replace($service->settingDefaults(), [
                'hero_layout' => 'probablue',
            ]),
        ]);

        $this->get(route('site.home'))
            ->assertOk()
            ->assertSee('data-home-layout="probablue"', false)
            ->assertSee('class="home-probablue-hero"', false)
            ->assertSee('home-probablue-panel--analysis', false)
            ->assertSee('home-probablue-panel--consultation', false)
            ->assertDontSee('id="before-after"', false)
            ->assertDontSee('id="dragme"', false);
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
            ->assertSee('name="settings[before_pattern]"', false)
            ->assertSee('name="settings[after_color_effect]"', false)
            ->assertSee('name="settings[before_color_effect]"', false)
            ->assertSee('name="settings[consultation_after_color_effect]"', false)
            ->assertSee('Renk Uygulaması')
            ->assertSee('Düz renk')
            ->assertSee('Gradyan')
            ->assertSee('data-homepage-pattern="carbon"', false)
            ->assertSee('Carbon Fiber')
            ->assertSee('Piksel Kareler')
            ->assertSee('Sembol çalışma biçimi')
            ->assertSee('İkisi birlikte')
            ->assertSee('Sol Panel Yüzeyi')
            ->assertSee('Sağ Panel Yüzeyi')
            ->assertSee('P-V Görsel Paleti')
            ->assertSee('data-homepage-computer-preview="true"', false)
            ->assertSee('name="settings[computer_pv_fill_mode]"', false)
            ->assertSee('name="settings[computer_pv_body_start_color]"', false)
            ->assertSee('name="settings[consultation_computer_pv_body_start_color]"', false)
            ->assertSee('P-PVT · Sabit görsel')
            ->assertSee('P-V · Canlı önizleme')
            ->assertSee('Tema duyarlı varsayılan SVG')
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
                'computer_pv_fill_mode' => 'gradient',
                'computer_pv_body_start_color' => '#102030',
                'computer_pv_body_end_color' => '#304050',
                'computer_pv_bar_dark_color' => '#aa2233',
                'consultation_computer_pv_fill_mode' => 'solid',
                'consultation_computer_pv_body_start_color' => '#204060',
                'tooltip_1_title_color' => '#334455',
                'after_color_effect' => 'solid',
                'before_color_effect' => 'gradient',
                'consultation_after_color_effect' => 'gradient',
                'before_pattern' => 'carbon',
                'before_pattern_color' => '#556677',
                'before_pattern_opacity' => 34,
                'before_pattern_scale' => 22,
                'before_pattern_blur' => 0.75,
                'before_pattern_blend' => 'overlay',
                'consultation_after_pattern' => 'micro-grid',
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
        $this->assertSame('#102030', $service->current()->fresh()->settings['computer_pv_body_start_color']);
        $this->assertSame('#204060', $service->current()->fresh()->settings['consultation_computer_pv_body_start_color']);
        $this->assertSame('solid', $service->current()->fresh()->settings['after_color_effect']);
        $this->assertSame('gradient', $service->current()->fresh()->settings['before_color_effect']);
        $this->assertSame('carbon', $service->current()->fresh()->settings['before_pattern']);
        $this->assertSame(34, $service->current()->fresh()->settings['before_pattern_opacity']);
        $this->assertSame(
            '0.34',
            $service->resolved('tr')['modes']['analysis']['styles']['--home-before-pattern-opacity']
        );
        $this->assertSame(
            '22px',
            $service->resolved('tr')['modes']['analysis']['styles']['--home-before-pattern-size']
        );
        $this->assertSame(
            '0.75px',
            $service->resolved('tr')['modes']['analysis']['styles']['--home-before-pattern-blur']
        );
        $this->assertSame(
            'overlay',
            $service->resolved('tr')['modes']['analysis']['styles']['--home-before-pattern-blend']
        );
        $this->assertStringStartsWith(
            'repeating-linear-gradient(',
            $service->resolved('tr')['modes']['analysis']['styles']['--home-before-pattern-image']
        );
        $this->assertStringStartsWith(
            'linear-gradient(',
            $service->resolved('tr')['modes']['consultation']['styles']['--home-after-pattern-image']
        );
        $this->assertSame(
            '#223344',
            $service->resolved('tr')['modes']['analysis']['styles']['--home-hero-after-text']
        );
        $this->assertSame(
            '#445566',
            $service->resolved('tr')['modes']['consultation']['styles']['--home-hero-before-text']
        );
        $this->assertSame(
            '#102030',
            $service->resolved('tr')['modes']['analysis']['styles']['--home-computer-frame']
        );
        $this->assertSame(
            '#304050',
            $service->resolved('tr')['modes']['analysis']['styles']['--home-computer-gradient-end']
        );
        $this->assertSame(
            '#204060',
            $service->resolved('tr')['modes']['consultation']['styles']['--home-computer-frame']
        );
        $this->assertSame(
            '#204060',
            $service->resolved('tr')['modes']['consultation']['styles']['--home-computer-gradient-end']
        );
        $this->assertSame('#334455', $service->resolved('tr')['tooltips'][0]['title_color']);
        $this->assertSame(
            'var(--home-after-bg)',
            $service->resolved('tr')['modes']['analysis']['styles']['--home-after-color-layer']
        );
        $this->assertSame(
            '1',
            $service->resolved('tr')['modes']['analysis']['styles']['--home-after-surface-opacity']
        );
        $this->assertStringStartsWith(
            'radial-gradient(',
            $service->resolved('tr')['modes']['analysis']['styles']['--home-before-color-layer']
        );

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
            ->assertSee('class="home-hero-pending home-background-loading"', false)
            ->assertSee(
                '<link rel="preload" as="image" href="'.$backgroundMedia->url().'" fetchpriority="high">',
                false
            )
            ->assertSee('data-home-background-url="'.$backgroundMedia->url().'"', false)
            ->assertSee('--home-background-image-dark:url(&quot;'.$backgroundMedia->url().'&quot;)', false)
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
            ->assertSee('--home-computer-frame:#102030', false)
            ->assertSee('--home-computer-alert:#aa2233', false)
            ->assertSee('--home-computer-gradient-end:#304050', false)
            ->assertSee('--home-after-color-layer:var(--home-after-bg)', false)
            ->assertSee('--home-after-surface-opacity:1', false)
            ->assertSee('--home-before-color-layer:radial-gradient(', false)
            ->assertSee('--home-before-pattern-opacity:0.34', false)
            ->assertSee('--home-before-pattern-size:22px', false)
            ->assertSee('--home-before-pattern-blur:0.75px', false)
            ->assertSee('--home-before-pattern-blend:overlay', false)
            ->assertSee('--home-before-pattern-image:repeating-linear-gradient(', false)
            ->assertSee('--home-tooltip-text: #334455', false);
    }

    public function test_cursor_symbols_can_run_while_moving_and_idle_together(): void
    {
        $service = app(HomepageConfigurationService::class);
        $payload = array_replace($service->contentDefaults(), [
            'settings' => array_replace($service->settingDefaults(), [
                'cursor_symbol_mode' => 'both',
            ]),
            'translations' => [],
        ]);

        $validator = Validator::make($payload, $service->validationRules());

        $this->assertFalse($validator->fails());

        $service->persist($validator->validated());

        $this->assertSame('both', $service->current()->fresh()->settings['cursor_symbol_mode']);
        $this->get('/')
            ->assertOk()
            ->assertSee('data-stat-symbol-mode="both"', false);
    }

    public function test_homepage_surface_patterns_are_allowlisted_and_bounded(): void
    {
        $service = app(HomepageConfigurationService::class);
        $payload = array_replace($service->contentDefaults(), [
            'settings' => array_replace($service->settingDefaults(), [
                'before_pattern' => 'url-javascript',
                'after_color_effect' => 'animated-rainbow',
                'before_pattern_opacity' => 90,
                'before_pattern_scale' => 2,
                'before_pattern_blur' => 12,
                'before_pattern_blend' => 'difference',
            ]),
            'translations' => [],
        ]);

        $validator = Validator::make($payload, $service->validationRules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('settings.before_pattern', $validator->errors()->toArray());
        $this->assertArrayHasKey('settings.after_color_effect', $validator->errors()->toArray());
        $this->assertArrayHasKey('settings.before_pattern_opacity', $validator->errors()->toArray());
        $this->assertArrayHasKey('settings.before_pattern_scale', $validator->errors()->toArray());
        $this->assertArrayHasKey('settings.before_pattern_blur', $validator->errors()->toArray());
        $this->assertArrayHasKey('settings.before_pattern_blend', $validator->errors()->toArray());
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
