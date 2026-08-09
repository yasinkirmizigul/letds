<?php

namespace Tests\Feature;

use App\Models\Site\SiteFaq;
use App\Models\Site\SiteLanguage;
use App\Models\Site\SiteNavigationItem;
use App\Models\Site\SitePage;
use App\Support\Site\SiteNavigationRoutes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteFaqAndAboutTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_page_and_public_navigation_are_bootstrapped(): void
    {
        $aboutPage = SitePage::query()->where('slug', 'hakkimizda')->firstOrFail();

        $this->assertTrue($aboutPage->isPublished());
        $this->assertDatabaseHas('site_navigation_items', [
            'location' => SiteNavigationItem::LOCATION_PRIMARY,
            'link_type' => SiteNavigationItem::LINK_TYPE_PAGE,
            'site_page_id' => $aboutPage->id,
            'title' => 'Hakkımızda',
        ]);
        $this->assertDatabaseHas('site_navigation_items', [
            'location' => SiteNavigationItem::LOCATION_PRIMARY,
            'link_type' => SiteNavigationItem::LINK_TYPE_ROUTE,
            'route_name' => SiteNavigationRoutes::FAQS,
        ]);

        $this->get($aboutPage->publicUrl('tr'))
            ->assertOk()
            ->assertSee('Hakkımızda')
            ->assertSee('İhtiyaca göre şekillenen bir çalışma modeli');

        $this->get(route('site.home'))
            ->assertOk()
            ->assertSee('Hikayemizi Keşfedin')
            ->assertSee('Tüm Soruları İncele')
            ->assertSee('Sıkça Sorulan Sorular');
    }

    public function test_public_faq_page_only_lists_active_global_records(): void
    {
        SiteFaq::query()->delete();
        $aboutPage = SitePage::query()->where('slug', 'hakkimizda')->firstOrFail();

        SiteFaq::query()->create([
            'group_label' => 'Süreç',
            'question' => 'Görünür global soru',
            'answer' => 'Bu yanıt ziyaretçiye gösterilir.',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        SiteFaq::query()->create([
            'group_label' => 'Süreç',
            'question' => 'Pasif global soru',
            'answer' => 'Bu yanıt gösterilmemelidir.',
            'is_active' => false,
            'sort_order' => 2,
        ]);
        SiteFaq::query()->create([
            'site_page_id' => $aboutPage->id,
            'group_label' => 'Sayfa',
            'question' => 'Sayfaya bağlı soru',
            'answer' => 'Yalnız bağlı olduğu CMS sayfasında kullanılmalıdır.',
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $this->get(route('site.faqs.index'))
            ->assertOk()
            ->assertSee('Görünür global soru')
            ->assertSee('Bu yanıt ziyaretçiye gösterilir.')
            ->assertDontSee('Pasif global soru')
            ->assertDontSee('Sayfaya bağlı soru')
            ->assertSee('FAQPage', false);
    }

    public function test_faq_page_can_search_and_filter_by_localized_group(): void
    {
        SiteFaq::query()->delete();

        SiteFaq::query()->create([
            'group_label' => 'Planlama',
            'question' => 'Teslim tarihi nasıl belirlenir?',
            'answer' => 'Kapsama göre gerçekçi bir takvim oluşturulur.',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        SiteFaq::query()->create([
            'group_label' => 'Ödeme',
            'question' => 'Ödeme adımları nelerdir?',
            'answer' => 'Ödeme planı teklif ile paylaşılır.',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->get(route('site.faqs.index', ['q' => 'takvim', 'group' => 'Planlama']))
            ->assertOk()
            ->assertSee('Teslim tarihi nasıl belirlenir?')
            ->assertDontSee('Ödeme adımları nelerdir?')
            ->assertSee('value="Planlama" selected', false);
    }

    public function test_localized_faq_route_uses_translated_content(): void
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

        SiteFaq::query()->delete();
        $faq = SiteFaq::query()->create([
            'group_label' => 'Süreç',
            'question' => 'Türkçe soru',
            'answer' => 'Türkçe yanıt',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $faq->translations()->create([
            'locale' => 'en',
            'group_label' => 'Process',
            'question' => 'How does the process work?',
            'answer' => 'We begin by clarifying the scope.',
        ]);

        $this->get(route('site.localized.faqs.index', ['locale' => 'en']))
            ->assertOk()
            ->assertSee('How does the process work?')
            ->assertSee('Process')
            ->assertDontSee('Türkçe soru');
    }
}
