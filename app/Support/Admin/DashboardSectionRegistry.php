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
                'children' => [
                    'hero_chips' => [
                        'label' => 'Bilgi chipleri',
                        'description' => 'Tarih, odak işi ve hızlı modül özetlerini gösterir.',
                        'default' => true,
                        'available' => true,
                    ],
                    'hero_quick_actions' => [
                        'label' => 'Hızlı aksiyon butonları',
                        'description' => 'Mesajlar, takvim ve yeni kayıt aksiyonlarını gösterir.',
                        'default' => true,
                        'available' => true,
                    ],
                    'hero_focus_list' => [
                        'label' => 'Odak listesi',
                        'description' => 'En hızlı aksiyon alınacak kayıt listesini gösterir.',
                        'default' => true,
                        'available' => true,
                    ],
                ],
            ],
            'kpi_overview' => [
                'label' => 'KPI kartları',
                'description' => 'Özet metrik kartlarını tek bakışta gösterir.',
                'icon' => 'ki-filled ki-chart-simple',
                'group' => 'Üst Alan',
                'default' => true,
                'available' => true,
                'children' => [
                    'kpi_content_pool' => [
                        'label' => 'İçerik havuzu kartı',
                        'description' => 'Blog, proje ve ürün toplamlarını gösterir.',
                        'default' => true,
                        'available' => true,
                    ],
                    'kpi_unread_messages' => [
                        'label' => 'Okunmamış mesaj kartı',
                        'description' => 'Okunmamış ve öncelikli mesaj özetini gösterir.',
                        'default' => true,
                        'available' => (bool) ($capabilities['messagesView'] ?? false),
                    ],
                    'kpi_weekly_appointments' => [
                        'label' => 'Haftalık randevu kartı',
                        'description' => 'Bu hafta ve bugün randevu yoğunluğunu gösterir.',
                        'default' => true,
                        'available' => (bool) ($capabilities['appointmentsView'] ?? false),
                    ],
                    'kpi_low_stock' => [
                        'label' => 'Düşük stok kartı',
                        'description' => 'Düşük stok uyarılarını öne çıkarır.',
                        'default' => true,
                        'available' => true,
                    ],
                    'kpi_system_alerts' => [
                        'label' => 'Sistem uyarısı kartı',
                        'description' => 'Log veya çöp kutusu tabanlı uyarı kartını gösterir.',
                        'default' => true,
                        'available' => true,
                    ],
                ],
            ],
            'module_overview' => [
                'label' => 'Hızlı erişim modülleri',
                'description' => 'Blog, ürün, proje ve diğer modüllere sayısal hızlı erişim sağlar.',
                'icon' => 'ki-filled ki-element-11',
                'group' => 'Operasyon',
                'default' => true,
                'available' => true,
                'children' => [
                    'module_blog' => [
                        'label' => 'Blog kartı',
                        'description' => 'Blog yönetim özet kartını gösterir.',
                        'default' => true,
                        'available' => (bool) ($capabilities['blogView'] ?? false),
                    ],
                    'module_projects' => [
                        'label' => 'Projeler kartı',
                        'description' => 'Proje yönetim özet kartını gösterir.',
                        'default' => true,
                        'available' => (bool) ($capabilities['projectsView'] ?? false),
                    ],
                    'module_products' => [
                        'label' => 'Ürünler kartı',
                        'description' => 'Ürün yönetim özet kartını gösterir.',
                        'default' => true,
                        'available' => (bool) ($capabilities['productsView'] ?? false),
                    ],
                    'module_messages' => [
                        'label' => 'Mesajlar kartı',
                        'description' => 'Mesaj kutusu özet kartını gösterir.',
                        'default' => true,
                        'available' => (bool) ($capabilities['messagesView'] ?? false),
                    ],
                    'module_appointments' => [
                        'label' => 'Randevular kartı',
                        'description' => 'Randevu planlama özet kartını gösterir.',
                        'default' => true,
                        'available' => (bool) ($capabilities['appointmentsView'] ?? false),
                    ],
                    'module_media' => [
                        'label' => 'Medya kartı',
                        'description' => 'Medya kütüphanesi özet kartını gösterir.',
                        'default' => true,
                        'available' => (bool) ($capabilities['mediaView'] ?? false),
                    ],
                    'module_galleries' => [
                        'label' => 'Galeri kartı',
                        'description' => 'Galeri modülü özet kartını gösterir.',
                        'default' => true,
                        'available' => (bool) ($capabilities['galleriesView'] ?? false),
                    ],
                    'module_categories' => [
                        'label' => 'Kategori kartı',
                        'description' => 'Kategori ağacı özet kartını gösterir.',
                        'default' => true,
                        'available' => (bool) ($capabilities['categoriesView'] ?? false),
                    ],
                    'module_users' => [
                        'label' => 'Kullanıcı kartı',
                        'description' => 'Kullanıcı ve admin dağılımını gösterir.',
                        'default' => true,
                        'available' => (bool) ($capabilities['usersView'] ?? false),
                    ],
                    'module_trash' => [
                        'label' => 'Silinenler kartı',
                        'description' => 'Çöp kutusu özet kartını gösterir.',
                        'default' => true,
                        'available' => (bool) ($capabilities['trashView'] ?? false),
                    ],
                    'module_audit' => [
                        'label' => 'Log kartı',
                        'description' => 'Sistem log özet kartını gösterir.',
                        'default' => true,
                        'available' => (bool) ($capabilities['auditView'] ?? false),
                    ],
                ],
            ],
            'activity_charts' => [
                'label' => 'Analiz ve grafikler',
                'description' => 'Aylık üretim, takip dağılımı ve plan yoğunluğu grafiklerini gösterir.',
                'icon' => 'ki-filled ki-chart-line',
                'group' => 'Analiz',
                'default' => true,
                'available' => true,
                'children' => [
                    'chart_monthly_activity' => [
                        'label' => 'Aylık aktivite grafiği',
                        'description' => 'Son 6 ay üretim ve talep hızını gösterir.',
                        'default' => true,
                        'available' => true,
                    ],
                    'chart_action_breakdown' => [
                        'label' => 'Takip dağılımı grafiği',
                        'description' => 'Odak işlerini kategori bazında dağıtır.',
                        'default' => true,
                        'available' => true,
                    ],
                    'chart_schedule_flow' => [
                        'label' => 'Randevu akış grafiği',
                        'description' => 'Önümüzdeki 7 günlük randevu yoğunluğunu gösterir.',
                        'default' => true,
                        'available' => (bool) ($capabilities['appointmentsView'] ?? false),
                    ],
                ],
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

            foreach ($definition['children'] ?? [] as $childKey => $childDefinition) {
                if (($childDefinition['available'] ?? false) !== true) {
                    continue;
                }

                $defaults[$childKey] = (bool) ($childDefinition['default'] ?? true);
            }
        }

        return $defaults;
    }

    public static function availableKeys(array $definitions): array
    {
        $keys = [];

        foreach ($definitions as $key => $definition) {
            if (($definition['available'] ?? false) !== true) {
                continue;
            }

            $keys[] = $key;

            foreach ($definition['children'] ?? [] as $childKey => $childDefinition) {
                if (($childDefinition['available'] ?? false) !== true) {
                    continue;
                }

                $keys[] = $childKey;
            }
        }

        return $keys;
    }
}
