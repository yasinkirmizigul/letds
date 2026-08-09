<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ABOUT_CONTENT = <<<'HTML'
<h2>İhtiyaca göre şekillenen bir çalışma modeli</h2>
<p>Her projeye hazır kalıplarla değil, hedefleri ve mevcut koşulları anlayarak başlıyoruz. Gereksinimleri birlikte netleştiriyor, uygulanabilir adımları görünür bir yol haritasına dönüştürüyoruz.</p>
<h2>Şeffaf, ölçülebilir ve sürdürülebilir</h2>
<p>Süreç boyunca düzenli iletişim kuruyor, alınan kararları ve ilerlemeyi takip edilebilir halde tutuyoruz. Amacımız yalnızca bir işi tamamlamak değil, sonrasında da güvenle kullanılabilecek kalıcı bir değer üretmek.</p>
<h2>Birlikte düşünür, birlikte geliştiririz</h2>
<p>İyi sonuçların açık iletişimden doğduğuna inanıyoruz. Soruları erkenden görünür kılıyor, geri bildirimleri sürecin doğal bir parçası olarak ele alıyor ve her aşamada ortak hedefe odaklanıyoruz.</p>
HTML;

    private const ABOUT_CONTENT_EN = <<<'HTML'
<h2>A working model shaped around your needs</h2>
<p>We begin by understanding your goals and current conditions instead of applying a fixed template. Together, we clarify requirements and turn them into a practical, visible roadmap.</p>
<h2>Transparent, measurable and sustainable</h2>
<p>We communicate regularly and keep decisions and progress traceable throughout the process. Our goal is not only to complete the work, but to create lasting value that can be used with confidence.</p>
<h2>We think and build together</h2>
<p>We believe strong outcomes grow from open communication. We surface questions early, treat feedback as a natural part of the process and stay aligned around the shared objective.</p>
HTML;

    private const SEEDED_FAQS = [
        [
            'group_label' => 'Hizmetler',
            'question' => 'Hangi konular için iletişime geçebilirim?',
            'answer' => 'İhtiyacınızı, hedefinizi veya çözmek istediğiniz problemi kısaca paylaşmanız yeterlidir. Talebinizi değerlendirip uygun çalışma biçimini ve sonraki adımları birlikte netleştiririz.',
            'group_label_en' => 'Services',
            'question_en' => 'What can I contact you about?',
            'answer_en' => 'A short description of your need, goal or the problem you want to solve is enough. We review your request and clarify the right way of working and the next steps together.',
        ],
        [
            'group_label' => 'Süreç',
            'question' => 'İlk görüşme ve çalışma süreci nasıl ilerliyor?',
            'answer' => 'Önce talebinizi ve önceliklerinizi dinliyoruz. Kapsamı netleştirdikten sonra zaman planını, sorumlulukları ve teslim adımlarını görünür hale getirerek düzenli bilgilendirmeyle ilerliyoruz.',
            'group_label_en' => 'Process',
            'question_en' => 'How do the first meeting and the project process work?',
            'answer_en' => 'We first listen to your request and priorities. Once the scope is clear, we make the timeline, responsibilities and delivery steps visible and keep you informed throughout the work.',
        ],
        [
            'group_label' => 'Süreç',
            'question' => 'İlk görüşme öncesinde ne hazırlamalıyım?',
            'answer' => 'Varsa mevcut dokümanlarınızı, örnekleri, hedef tarihinizi ve öncelikli beklentilerinizi paylaşabilirsiniz. Hazır bir briefiniz yoksa sorun değil; doğru sorularla kapsamı birlikte oluşturabiliriz.',
            'group_label_en' => 'Process',
            'question_en' => 'What should I prepare before the first meeting?',
            'answer_en' => 'You can share existing documents, examples, your target date and key expectations. A ready brief is not required; we can define the scope together with the right questions.',
        ],
        [
            'group_label' => 'Planlama',
            'question' => 'Proje süresi ve teslim tarihi nasıl belirleniyor?',
            'answer' => 'Süre; kapsam, öncelik, gerekli içerikler ve geri bildirim adımlarına göre belirlenir. Başlangıçta gerçekçi bir takvim oluşturur, kapsam değişikliklerinin takvime etkisini şeffaf biçimde paylaşırız.',
            'group_label_en' => 'Planning',
            'question_en' => 'How are the project duration and delivery date determined?',
            'answer_en' => 'Timing depends on scope, priority, required content and feedback stages. We set a realistic schedule at the start and communicate transparently when scope changes affect it.',
        ],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('site_pages') || ! Schema::hasTable('site_navigation_items')) {
            return;
        }

        $now = now();
        $aboutPage = DB::table('site_pages')->where('slug', 'hakkimizda')->first();

        if (! $aboutPage) {
            $aboutPageId = DB::table('site_pages')->insertGetId([
                'title' => 'Hakkımızda',
                'slug' => 'hakkimizda',
                'hero_kicker' => 'Bizi Tanıyın',
                'excerpt' => 'Karmaşık ihtiyaçları anlaşılır planlara, iyi fikirleri sürdürülebilir sonuçlara dönüştürmek için birlikte çalışıyoruz.',
                'content' => self::ABOUT_CONTENT,
                'icon_class' => 'ki-outline ki-people',
                'meta_title' => 'Hakkımızda',
                'meta_description' => 'Çalışma yaklaşımımızı, değerlerimizi ve projeleri nasıl birlikte ilerlettiğimizi keşfedin.',
                'meta_keywords' => 'hakkımızda, çalışma yaklaşımı, proje süreci',
                'show_faqs' => false,
                'show_counters' => false,
                'is_featured' => false,
                'is_active' => true,
                'sort_order' => 1,
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]);
        } else {
            $aboutPageId = (int) $aboutPage->id;
        }

        $this->insertAboutTranslation($aboutPageId, $now);
        $this->insertNavigationItems($aboutPageId, $now);
        $this->insertStarterFaqs($now);
    }

    public function down(): void
    {
        if (Schema::hasTable('site_navigation_items')) {
            DB::table('site_navigation_items')
                ->where('link_type', 'route')
                ->where('route_name', 'site.faqs.index')
                ->delete();
        }

        if (Schema::hasTable('site_faqs')) {
            DB::table('site_faqs')
                ->whereNull('site_page_id')
                ->whereIn('question', array_column(self::SEEDED_FAQS, 'question'))
                ->delete();
        }

        if (! Schema::hasTable('site_pages')) {
            return;
        }

        $aboutPage = DB::table('site_pages')
            ->where('slug', 'hakkimizda')
            ->where('title', 'Hakkımızda')
            ->where('content', self::ABOUT_CONTENT)
            ->first();

        if (! $aboutPage) {
            return;
        }

        if (Schema::hasTable('site_navigation_items')) {
            DB::table('site_navigation_items')->where('site_page_id', $aboutPage->id)->delete();
        }

        DB::table('site_pages')->where('id', $aboutPage->id)->delete();
    }

    private function insertAboutTranslation(int $aboutPageId, mixed $now): void
    {
        if (! Schema::hasTable('site_page_translations')
            || DB::table('site_page_translations')->where('site_page_id', $aboutPageId)->where('locale', 'en')->exists()
            || DB::table('site_page_translations')->where('slug', 'about-us')->exists()) {
            return;
        }

        DB::table('site_page_translations')->insert([
            'site_page_id' => $aboutPageId,
            'locale' => 'en',
            'title' => 'About Us',
            'slug' => 'about-us',
            'hero_kicker' => 'Get to Know Us',
            'excerpt' => 'We work together to turn complex needs into clear plans and strong ideas into sustainable outcomes.',
            'content' => self::ABOUT_CONTENT_EN,
            'meta_title' => 'About Us',
            'meta_description' => 'Discover our working approach, values and how we move projects forward together.',
            'meta_keywords' => 'about us, working approach, project process',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function insertNavigationItems(int $aboutPageId, mixed $now): void
    {
        foreach (['primary', 'footer'] as $location) {
            $aboutNavigationId = DB::table('site_navigation_items')
                ->where('location', $location)
                ->where('link_type', 'page')
                ->where('site_page_id', $aboutPageId)
                ->value('id');

            if (! $aboutNavigationId) {
                $aboutNavigationId = DB::table('site_navigation_items')->insertGetId([
                    'location' => $location,
                    'parent_id' => null,
                    'site_page_id' => $aboutPageId,
                    'title' => 'Hakkımızda',
                    'icon_class' => 'ki-outline ki-people',
                    'link_type' => 'page',
                    'url' => null,
                    'route_name' => null,
                    'target' => '_self',
                    'is_active' => true,
                    'sort_order' => $this->nextSortOrder($location),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $faqNavigationId = DB::table('site_navigation_items')
                ->where('location', $location)
                ->where('link_type', 'route')
                ->where('route_name', 'site.faqs.index')
                ->value('id');

            if (! $faqNavigationId) {
                $faqNavigationId = DB::table('site_navigation_items')->insertGetId([
                    'location' => $location,
                    'parent_id' => null,
                    'site_page_id' => null,
                    'title' => 'Sıkça Sorulan Sorular',
                    'icon_class' => 'ki-outline ki-message-question',
                    'link_type' => 'route',
                    'url' => null,
                    'route_name' => 'site.faqs.index',
                    'target' => '_self',
                    'is_active' => true,
                    'sort_order' => $this->nextSortOrder($location),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $this->insertNavigationTranslation((int) $aboutNavigationId, 'About Us', $now);
            $this->insertNavigationTranslation((int) $faqNavigationId, 'Frequently Asked Questions', $now);
        }
    }

    private function insertStarterFaqs(mixed $now): void
    {
        if (! Schema::hasTable('site_faqs')
            || DB::table('site_faqs')->whereNull('site_page_id')->exists()) {
            return;
        }

        foreach (self::SEEDED_FAQS as $index => $faq) {
            $faqId = DB::table('site_faqs')->insertGetId([
                'site_page_id' => null,
                'group_label' => $faq['group_label'],
                'question' => $faq['question'],
                'answer' => $faq['answer'],
                'icon_class' => 'ki-outline ki-message-question',
                'is_active' => true,
                'sort_order' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if (Schema::hasTable('site_faq_translations')) {
                DB::table('site_faq_translations')->insert([
                    'site_faq_id' => $faqId,
                    'locale' => 'en',
                    'group_label' => $faq['group_label_en'],
                    'question' => $faq['question_en'],
                    'answer' => $faq['answer_en'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    private function insertNavigationTranslation(int $navigationId, string $title, mixed $now): void
    {
        if (! Schema::hasTable('site_navigation_item_translations')
            || DB::table('site_navigation_item_translations')
                ->where('site_navigation_item_id', $navigationId)
                ->where('locale', 'en')
                ->exists()) {
            return;
        }

        DB::table('site_navigation_item_translations')->insert([
            'site_navigation_item_id' => $navigationId,
            'locale' => 'en',
            'title' => $title,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function nextSortOrder(string $location): int
    {
        return ((int) DB::table('site_navigation_items')->where('location', $location)->max('sort_order')) + 1;
    }
};
