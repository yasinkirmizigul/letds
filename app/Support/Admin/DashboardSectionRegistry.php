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
                'label' => 'Özet metrik kartları',
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
                        'description' => 'Sistem kaydı veya çöp kutusu tabanlı uyarı kartını gösterir.',
                        'default' => true,
                        'available' => true,
                    ],
                    'kpi_monthly_revenue' => [
                        'label' => '30 günlük ciro kartı',
                        'description' => 'E-ticaret siparişlerinden son 30 günlük ciroyu gösterir.',
                        'default' => true,
                        'available' => (bool) ($capabilities['ecommerceOrdersView'] ?? false),
                    ],
                    'kpi_payment_health' => [
                        'label' => 'Ödeme uyarısı kartı',
                        'description' => 'Ödeme entegrasyonu ve ödeme hareketi uyarılarını gösterir.',
                        'default' => true,
                        'available' => (bool) ($capabilities['sitePaymentsView'] ?? false),
                    ],
                    'kpi_member_health' => [
                        'label' => 'Üyelik kartı',
                        'description' => 'Aktif ve yeni üyelik özetini gösterir.',
                        'default' => true,
                        'available' => (bool) ($capabilities['membersView'] ?? false),
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
                    'module_ecommerce' => [
                        'label' => 'E-ticaret kartı',
                        'description' => 'Sipariş ve ciro özet kartını gösterir.',
                        'default' => true,
                        'available' => (bool) ($capabilities['ecommerceOrdersView'] ?? false),
                    ],
                    'module_messages' => [
                        'label' => 'Mesajlar kartı',
                        'description' => 'Mesaj kutusu özet kartını gösterir.',
                        'default' => true,
                        'available' => (bool) ($capabilities['messagesView'] ?? false),
                    ],
                    'module_members' => [
                        'label' => 'Üyelikler kartı',
                        'description' => 'Üyelik yönetimi özet kartını gösterir.',
                        'default' => true,
                        'available' => (bool) ($capabilities['membersView'] ?? false),
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
                        'label' => 'Sistem kaydı kartı',
                        'description' => 'Sistem kayıtları özet kartını gösterir.',
                        'default' => true,
                        'available' => (bool) ($capabilities['auditView'] ?? false),
                    ],
                    'module_site_health' => [
                        'label' => 'Site sağlığı kartı',
                        'description' => 'Site ayarları ve yayın hazırlığı özet kartını gösterir.',
                        'default' => true,
                        'available' => (bool) ($capabilities['siteSettingsView'] ?? false),
                    ],
                    'module_payments' => [
                        'label' => 'Ödemeler kartı',
                        'description' => 'Ödeme entegrasyonları özet kartını gösterir.',
                        'default' => true,
                        'available' => (bool) ($capabilities['sitePaymentsView'] ?? false),
                    ],
                ],
            ],
            'operations_health' => [
                'label' => 'Operasyon sağlığı',
                'description' => 'Sipariş, ödeme, site, içerik, stok, üyelik ve destek sağlığını tek blokta gösterir.',
                'icon' => 'ki-filled ki-pulse',
                'group' => 'Operasyon',
                'default' => true,
                'available' => true,
                'children' => [
                    'health_ecommerce' => [
                        'label' => 'E-ticaret sağlığı',
                        'description' => 'Sipariş ve ciro sağlığı kartını gösterir.',
                        'default' => true,
                        'available' => (bool) ($capabilities['ecommerceOrdersView'] ?? false),
                    ],
                    'health_payments' => [
                        'label' => 'Ödeme sağlığı',
                        'description' => 'Ödeme entegrasyonu ve hareket uyarılarını gösterir.',
                        'default' => true,
                        'available' => (bool) ($capabilities['sitePaymentsView'] ?? false),
                    ],
                    'health_site' => [
                        'label' => 'Site hazırlığı',
                        'description' => 'SEO dosyaları, SMTP ve bakım modu sağlığını gösterir.',
                        'default' => true,
                        'available' => (bool) ($capabilities['siteSettingsView'] ?? false),
                    ],
                    'health_content' => [
                        'label' => 'İçerik kalitesi',
                        'description' => 'SEO, görsel ve taslak içerik risklerini özetler.',
                        'default' => true,
                        'available' => (bool) (
                            ($capabilities['blogView'] ?? false)
                            || ($capabilities['projectsView'] ?? false)
                            || ($capabilities['productsView'] ?? false)
                            || ($capabilities['sitePagesView'] ?? false)
                        ),
                    ],
                    'health_inventory' => [
                        'label' => 'Stok ve katalog',
                        'description' => 'Stok, fiyat ve ürün görsel risklerini gösterir.',
                        'default' => true,
                        'available' => (bool) ($capabilities['productsView'] ?? false),
                    ],
                    'health_support' => [
                        'label' => 'Mesaj SLA',
                        'description' => 'Geciken ve öncelikli mesajları gösterir.',
                        'default' => true,
                        'available' => (bool) ($capabilities['messagesView'] ?? false),
                    ],
                    'health_members' => [
                        'label' => 'Üyelik sağlığı',
                        'description' => 'Aktif, yeni ve askıdaki üyelikleri gösterir.',
                        'default' => true,
                        'available' => (bool) ($capabilities['membersView'] ?? false),
                    ],
                    'health_appointments' => [
                        'label' => 'Randevu performansı',
                        'description' => 'Tamamlanan, iptal edilen ve kaçırılan randevuları gösterir.',
                        'default' => true,
                        'available' => (bool) ($capabilities['appointmentsView'] ?? false),
                    ],
                    'health_translations' => [
                        'label' => 'Çeviri kapsamı',
                        'description' => 'Aktif diller için eksik çeviri durumunu gösterir.',
                        'default' => true,
                        'available' => (bool) ($capabilities['siteLanguagesView'] ?? false),
                    ],
                ],
            ],
            'commerce_flow' => [
                'label' => 'Sipariş akışı',
                'description' => 'Sipariş işlem kuyruğu ve son siparişleri gösterir.',
                'icon' => 'ki-filled ki-basket',
                'group' => 'Operasyon',
                'default' => true,
                'available' => (bool) ($capabilities['ecommerceOrdersView'] ?? false),
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
                        'description' => 'Son 6 ay üretim, talep, sipariş ve üyelik hızını gösterir.',
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
            'risk_center' => [
                'label' => 'Risk merkezi',
                'description' => 'Ödeme, site, içerik, stok, mesaj ve çeviri risklerini aksiyon listesi olarak gösterir.',
                'icon' => 'ki-filled ki-notification-status',
                'group' => 'Akış Panelleri',
                'default' => true,
                'available' => true,
            ],
            'audit_issues' => [
                'label' => 'Sistem uyarıları',
                'description' => 'Son 4xx ve 5xx sistem kayıtlarını ayrı panelde gösterir.',
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
