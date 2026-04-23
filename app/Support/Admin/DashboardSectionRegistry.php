<?php

namespace App\Support\Admin;

class DashboardSectionRegistry
{
    public static function definitions(array $capabilities = []): array
    {
        return [
            'hero_overview' => [
                'label' => 'Karşılama ve odak alanı',
                'description' => 'Üst karşılama alanı, odak listesi ve hızlı aksiyon blokları.',
                'icon' => 'ki-filled ki-home-2',
                'group' => 'Üst Alan',
                'default' => true,
                'available' => true,
            ],
            'kpi_overview' => [
                'label' => 'KPI kartları',
                'description' => 'Özet metrik kartlarını tek bakışta gösterir.',
                'icon' => 'ki-filled ki-chart-simple',
                'group' => 'Üst Alan',
                'default' => true,
                'available' => true,
            ],
            'module_overview' => [
                'label' => 'Hızlı erişim modülleri',
                'description' => 'Blog, ürün, proje ve diğer modüllere sayısal hızlı erişim sağlar.',
                'icon' => 'ki-filled ki-element-11',
                'group' => 'Operasyon',
                'default' => true,
                'available' => true,
            ],
            'activity_charts' => [
                'label' => 'Analiz ve grafikler',
                'description' => 'Aylık üretim, takip dağılımı ve plan yoğunluğu grafiklerini gösterir.',
                'icon' => 'ki-filled ki-chart-line',
                'group' => 'Analiz',
                'default' => true,
                'available' => true,
            ],
            'recent_messages' => [
                'label' => 'Son mesajlar',
                'description' => 'Yeni mesajları ve geri dönüş bekleyen talepleri listeler.',
                'icon' => 'ki-filled ki-messages',
                'group' => 'Akış Panelleri',
                'default' => true,
                'available' => (bool) ($capabilities['messagesView'] ?? false),
            ],
            'upcoming_appointments' => [
                'label' => 'Yaklaşan randevular',
                'description' => 'Takvimde yaklaşan görüşmeleri hızlıca takip etmeni sağlar.',
                'icon' => 'ki-filled ki-calendar-8',
                'group' => 'Akış Panelleri',
                'default' => true,
                'available' => (bool) ($capabilities['appointmentsView'] ?? false),
            ],
            'recent_content' => [
                'label' => 'Son güncellenen içerikler',
                'description' => 'Son düzenlenen içerikleri açıp yarım kalan işi devam ettirmeni sağlar.',
                'icon' => 'ki-filled ki-notepad-edit',
                'group' => 'Akış Panelleri',
                'default' => true,
                'available' => true,
            ],
            'audit_issues' => [
                'label' => 'Sistem uyarıları',
                'description' => 'Son 4xx ve 5xx log kayıtlarını ayrı panelde gösterir.',
                'icon' => 'ki-filled ki-fingerprint-scanning',
                'group' => 'Akış Panelleri',
                'default' => true,
                'available' => (bool) ($capabilities['auditView'] ?? false),
            ],
        ];
    }

    public static function defaults(array $definitions): array
    {
        $defaults = [];

        foreach ($definitions as $key => $definition) {
            if (($definition['available'] ?? false) !== true) {
                continue;
            }

            $defaults[$key] = (bool) ($definition['default'] ?? true);
        }

        return $defaults;
    }
}
