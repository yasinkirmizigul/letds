<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_homepage_sections', function (Blueprint $table) {
            $table->string('placement', 40)->default('homepage')->after('type')->index();
        });

        $now = now();
        $serviceSectionId = DB::table('site_homepage_sections')->insertGetId([
            'type' => 'services',
            'placement' => 'services',
            'eyebrow' => 'Uzmanlık Alanlarımız',
            'title' => 'Hizmetlerimiz',
            'description' => 'Araştırma fikrinden sonuçların raporlanmasına kadar ihtiyaç duyduğunuz istatistiksel desteği tek bir süreçte sunuyoruz.',
            'settings' => json_encode([
                'columns' => 3,
                'alignment' => 'left',
                'surface' => 'light',
                'accent_color' => '#087cf0',
            ], JSON_THROW_ON_ERROR),
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $processSectionId = DB::table('site_homepage_sections')->insertGetId([
            'type' => 'process',
            'placement' => 'services',
            'eyebrow' => 'Nasıl Çalışıyoruz?',
            'title' => 'Şeffaf ve planlı bir analiz süreci',
            'description' => 'Her adımın kapsamını birlikte netleştiriyor, ilerlemeyi görünür ve anlaşılır tutuyoruz.',
            'settings' => json_encode([
                'columns' => 3,
                'alignment' => 'left',
                'surface' => 'tint',
                'accent_color' => '#087cf0',
            ], JSON_THROW_ON_ERROR),
            'is_active' => true,
            'sort_order' => 2,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $services = [
            ['Araştırma Tasarımı', 'Araştırma sorusunun netleştirilmesi, uygun yöntemin seçimi ve örneklem planının oluşturulması.', 'blueprint'],
            ['Veri Analizi', 'Veri yapınıza uygun tanımlayıcı ve ileri istatistiksel analizlerin güvenle yürütülmesi.', 'chart'],
            ['Makale Analizleri', 'Akademik yayın sürecine uygun analiz, tablo, bulgu ve yöntem desteği.', 'document'],
            ['Raporlama', 'Sonuçların anlaşılır grafikler, tablolar ve karar odaklı yorumlarla sunulması.', 'report'],
            ['Klinik Araştırmalar', 'Klinik veri yapıları için metodolojik planlama ve standartlara uygun istatistiksel destek.', 'health'],
            ['Yapay Zeka ve Veri Bilimi', 'Tahminleme, sınıflandırma ve veri odaklı otomasyon ihtiyaçları için ölçeklenebilir çözümler.', 'ai'],
        ];

        $process = [
            ['Ön Görüşme', 'İhtiyaç, hedef ve mevcut veri yapısını birlikte değerlendiririz.', 'conversation'],
            ['Proje Değerlendirmesi', 'Kapsamı, riskleri, teslimleri ve gerçekçi takvimi netleştiririz.', 'search'],
            ['Analiz Planı', 'Uygulanacak yöntemleri ve kontrol adımlarını yazılı hale getiririz.', 'blueprint'],
            ['Veri Analizi', 'Analizleri izlenebilir ve tekrarlanabilir bir akışla yürütürüz.', 'chart'],
            ['Rapor ve Yorumlama', 'Bulguları anlaşılır, karar vermeyi kolaylaştıran bir rapora dönüştürürüz.', 'report'],
            ['Revizyon Desteği', 'Teslim sonrası soruları ve gerekli iyileştirmeleri birlikte tamamlarız.', 'support'],
        ];

        foreach ([[$serviceSectionId, $services], [$processSectionId, $process]] as [$sectionId, $items]) {
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

        foreach (['primary', 'footer'] as $location) {
            if (DB::table('site_navigation_items')
                ->where('location', $location)
                ->where('route_name', 'site.services.index')
                ->doesntExist()) {
                DB::table('site_navigation_items')->insert([
                    'location' => $location,
                    'parent_id' => null,
                    'site_page_id' => null,
                    'title' => 'Hizmetler',
                    'icon_class' => 'ki-outline ki-briefcase',
                    'link_type' => 'route',
                    'url' => null,
                    'route_name' => 'site.services.index',
                    'target' => '_self',
                    'is_active' => true,
                    'sort_order' => ((int) DB::table('site_navigation_items')->where('location', $location)->max('sort_order')) + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('site_navigation_items')->where('route_name', 'site.services.index')->delete();
        DB::table('site_homepage_sections')->where('placement', 'services')->delete();

        Schema::table('site_homepage_sections', function (Blueprint $table) {
            $table->dropColumn('placement');
        });
    }
};
