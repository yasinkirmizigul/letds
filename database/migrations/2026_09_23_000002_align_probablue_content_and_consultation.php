<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->string('support_topic', 190)->nullable()->after('meeting_method_id');
        });

        $this->updateHomepageContent([
            'browser_title' => 'PROBABLUE | İstatistiksel Analiz ve Danışmanlık',
            'analysis_tab_label' => 'İstatistiksel Analiz ve Danışmanlık',
            'hero_title' => 'Akademik ve profesyonel projeleriniz için istatistiksel danışmanlık.',
            'cta_label' => 'İstatistiksel Analiz ve Danışmanlık',
            'cta_url' => '#neler-sunuyoruz',
            'tooltip_1_title' => 'Araştırma tasarımınızı bilimsel yöntemlerle güçlendirin.',
            'tooltip_1_highlighted_title' => 'Araştırma tasarımınızı <span class="color-main">bilimsel yöntemlerle</span> güçlendirin.',
            'tooltip_2_title' => 'Verinizi güvenilir ve tekrarlanabilir analizlerle değerlendirin.',
            'tooltip_2_highlighted_title' => 'Verinizi <span class="color-main">güvenilir ve tekrarlanabilir</span> analizlerle değerlendirin.',
            'tooltip_3_title' => 'Bulgularınızı anlaşılır tablo ve grafiklerle raporlayın.',
            'tooltip_3_highlighted_title' => 'Bulgularınızı <span class="color-main">anlaşılır tablo ve grafiklerle</span> raporlayın.',
            'tooltip_4_title' => 'Tez ve makale sürecinizde uzman desteği alın.',
            'tooltip_4_highlighted_title' => 'Tez ve makale sürecinizde <span class="color-main">uzman desteği</span> alın.',
        ]);

        DB::table('site_homepage_sections')
            ->where('placement', 'homepage')
            ->where('type', 'features')
            ->update(['is_active' => false, 'updated_at' => now()]);

        $this->replaceManagedSection(
            'services',
            [
                'eyebrow' => '01 — Hizmetler',
                'title' => 'Araştırmanızın her aşamasında yanınızdayız.',
                'description' => 'Bilimsel yönteme dayalı, ihtiyaçlarınıza özel ve şeffaf danışmanlık çözümleri.',
                'settings' => ['columns' => 3, 'alignment' => 'left', 'surface' => 'light', 'accent_color' => '#087cf0'],
            ],
            [
                ['Araştırma Tasarımı', "Örneklem büyüklüğü\nGüç analizi\nRandomizasyon\nİstatistiksel yöntem seçimi\nAnaliz planının oluşturulması", 'blueprint'],
                ['İstatistiksel Veri Analizi', "Tanımlayıcı istatistikler\nGrup karşılaştırmaları\nKorelasyon analizleri\nRegresyon analizleri\nParametrik ve nonparametrik testler", 'chart'],
                ['İleri İstatistiksel Analizler', "Karma dizayn ve tekrarlı ölçüm analizleri\nÇok değişkenli modeller\nROC ve tanı performansı\nSağkalım analizleri\nBibliyografik analizler\nYapısal eşitlik modelleri", 'sparkles'],
                ['Ölçek Geliştirme & Uyarlama', "Madde analizi\nGüvenilirlik analizleri\nAçımlayıcı ve doğrulayıcı faktör analizleri\nGeçerlilik analizleri", 'adjustments'],
                ['Akademik Raporlama', "Tablo ve grafiklerin hazırlanması\nBulguların yorumlanması\nİstatistiksel yöntem yazımı\nAPA uyumlu raporlama", 'report'],
                ['Makale & Tez Desteği', "Tez ve makale analizleri\nAnalizlerin gözden geçirilmesi\nHakem taleplerinin değerlendirilmesi\nEk analizler\nYayın öncesi kontrol", 'document'],
            ],
        );

        $this->replaceManagedSection(
            'process',
            [
                'eyebrow' => '02 — Süreç',
                'title' => 'Basit, şeffaf ve bilimsel bir süreç.',
                'description' => 'Her aşamada ne yapıldığını, hangi çıktının üretildiğini ve sonraki adımı açıkça görürsünüz.',
                'settings' => ['columns' => 2, 'alignment' => 'left', 'surface' => 'tint', 'accent_color' => '#087cf0'],
            ],
            [
                ['Ön Görüşme & Analiz Planı', 'Araştırmanın amacı, hipotezleri, veri yapısı ve çalışma tasarımı birlikte değerlendirilir. Uygun istatistiksel yöntemler belirlenerek analiz planı ve ihtiyaç duyulan çıktılar netleştirilir.', 'conversation'],
                ['Veri Analizi', 'Veri seti kontrol edilir, gerekli düzenlemeler planlanır ve onaylanan yöntemlerle analizler yürütülür. Süreçte kullanılan testler ve temel kararlar izlenebilir biçimde kayıt altına alınır.', 'chart'],
                ['Raporlama', 'Sonuçlar tablo ve grafiklerle sunulur; p değerleri, güven aralıkları ve etki büyüklükleriyle birlikte yorumlanır. Yöntem ve bulgular akademik yazıma ve APA 7 ilkelerine uygun hazırlanır.', 'report'],
                ['Revizyon Desteği', 'Teslim sonrasında çalışma kapsamındaki sorular, danışman veya hakem geri bildirimleri değerlendirilir; gerekli açıklamalar ve ek analizler birlikte tamamlanır.', 'support'],
            ],
        );

        DB::table('users')->where('name', 'Ece Yılmaz')->update([
            'name' => 'Aylin Alboyacı',
            'title' => 'İstatistiksel Analiz Uzmanı',
            'updated_at' => now(),
        ]);
        DB::table('users')->where('name', 'Mert Kaya')->update([
            'name' => 'Ayça Ölmez Binzat',
            'title' => 'Araştırma ve İstatistik Danışmanı',
            'updated_at' => now(),
        ]);
        DB::table('users')->where('name', 'Selin Arslan')->update([
            'is_active' => false,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('site_homepage_sections')
            ->where('placement', 'homepage')
            ->where('type', 'features')
            ->update(['is_active' => true, 'updated_at' => now()]);

        $this->updateHomepageContent([
            'browser_title' => '',
            'analysis_tab_label' => 'İstatistiksel Analiz',
            'hero_title' => 'The combination of great design and diligent app development.',
            'cta_label' => 'VIEW THEMES',
            'cta_url' => '',
        ]);

        DB::table('users')->where('name', 'Aylin Alboyacı')->update(['name' => 'Ece Yılmaz', 'title' => 'Veri Analizi Uzmanı']);
        DB::table('users')->where('name', 'Ayça Ölmez Binzat')->update(['name' => 'Mert Kaya', 'title' => 'Araştırma Danışmanı']);
        DB::table('users')->where('name', 'Selin Arslan')->update(['is_active' => true]);

        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropColumn('support_topic');
        });
    }

    private function updateHomepageContent(array $values): void
    {
        $config = DB::table('site_homepage_configs')->where('key', 'concept-home')->first();

        if (! $config) {
            return;
        }

        $content = json_decode((string) $config->content, true) ?: [];

        DB::table('site_homepage_configs')->where('id', $config->id)->update([
            'content' => json_encode(array_replace($content, $values), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'updated_at' => now(),
        ]);
    }

    private function replaceManagedSection(string $type, array $section, array $items): void
    {
        $existing = DB::table('site_homepage_sections')
            ->where('placement', 'services')
            ->where('type', $type)
            ->first();
        $now = now();
        $payload = [
            'type' => $type,
            'placement' => 'services',
            'eyebrow' => $section['eyebrow'],
            'title' => $section['title'],
            'description' => $section['description'],
            'settings' => json_encode($section['settings'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'is_active' => true,
            'sort_order' => $type === 'services' ? 1 : 2,
            'updated_at' => $now,
        ];

        if ($existing) {
            DB::table('site_homepage_sections')->where('id', $existing->id)->update($payload);
            $sectionId = $existing->id;
        } else {
            $sectionId = DB::table('site_homepage_sections')->insertGetId($payload + ['created_at' => $now]);
        }

        DB::table('site_homepage_section_items')->where('site_homepage_section_id', $sectionId)->delete();

        foreach ($items as $index => [$title, $description, $icon]) {
            DB::table('site_homepage_section_items')->insert([
                'site_homepage_section_id' => $sectionId,
                'title' => $title,
                'description' => $description,
                'icon' => $icon,
                'link_label' => null,
                'link_url' => null,
                'is_active' => true,
                'sort_order' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
