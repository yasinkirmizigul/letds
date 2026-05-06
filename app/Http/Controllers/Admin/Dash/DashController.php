<?php

namespace App\Http\Controllers\Admin\Dash;

use App\Http\Controllers\Controller;
use App\Models\Admin\AuditLog\AuditLog;
use App\Models\Admin\BlogPost\BlogPost;
use App\Models\Admin\Category;
use App\Models\Admin\Ecommerce\EcommerceOrder;
use App\Models\Admin\Ecommerce\EcommerceOrderTransaction;
use App\Models\Admin\Gallery\Gallery;
use App\Models\Admin\Media\Media;
use App\Models\Admin\Product\Product;
use App\Models\Admin\Project\Project;
use App\Models\Admin\User\User;
use App\Models\Appointment\Appointment;
use App\Models\ContactMessage;
use App\Models\Member;
use App\Models\Site\PaymentIntegration;
use App\Models\Site\SiteLanguage;
use App\Models\Site\SitePage;
use App\Models\Site\SiteSetting;
use App\Support\Admin\DashboardSectionRegistry;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = auth()->user();
        $now = Carbon::now('Europe/Istanbul');

        [$monthLabels, $monthKeys, $monthStart] = $this->monthBuckets($now, 6);
        [$dayLabels, $dayKeys, $dayStart] = $this->dayBuckets($now, 7);

        $can = [
            'blogView' => $this->can($user, 'blog.view'),
            'blogCreate' => $this->can($user, 'blog.create'),
            'projectsView' => $this->can($user, 'projects.view'),
            'projectsCreate' => $this->can($user, 'projects.create'),
            'productsView' => $this->can($user, 'products.view'),
            'productsCreate' => $this->can($user, 'products.create'),
            'categoriesView' => $this->can($user, 'categories.view'),
            'categoriesCreate' => $this->can($user, 'categories.create'),
            'mediaView' => $this->can($user, 'media.view'),
            'mediaCreate' => $this->can($user, 'media.create'),
            'galleriesView' => $this->can($user, 'galleries.view'),
            'galleriesCreate' => $this->can($user, 'galleries.create'),
            'usersView' => $this->can($user, 'users.view'),
            'trashView' => $this->can($user, 'trash.view'),
            'auditView' => $this->can($user, 'audit-logs.view'),
            'appointmentsView' => $this->can($user, 'appointments.view'),
            'ecommerceOrdersView' => $this->can($user, 'ecommerce_orders.view'),
            'ecommerceOrdersCreate' => $this->can($user, 'ecommerce_orders.create'),
            'membersView' => $this->can($user, 'members.view'),
            'sitePaymentsView' => $this->can($user, 'site_payments.view'),
            'siteSettingsView' => $this->can($user, 'site_settings.view'),
            'siteLanguagesView' => $this->can($user, 'site_languages.view'),
            'sitePagesView' => $this->can($user, 'site_pages.view'),
            'messagesView' => $user->canAccessAdmin(),
        ];

        $blogStats = [
            'total' => $can['blogView'] ? BlogPost::query()->count() : 0,
            'published' => $can['blogView'] ? BlogPost::query()->published()->count() : 0,
            'draft' => $can['blogView'] ? BlogPost::query()->draft()->count() : 0,
            'featured' => $can['blogView'] ? BlogPost::query()->featured()->count() : 0,
        ];

        $projectStats = [
            'total' => $can['projectsView'] ? Project::query()->count() : 0,
            'public' => $can['projectsView'] ? Project::query()->publicVisible()->count() : 0,
            'featured' => $can['projectsView'] ? Project::query()->featured()->count() : 0,
            'active' => $can['projectsView'] ? Project::query()->where('status', Project::STATUS_ACTIVE)->count() : 0,
        ];

        $productStats = [
            'total' => $can['productsView'] ? Product::query()->count() : 0,
            'active' => $can['productsView'] ? Product::query()->where('is_active', true)->count() : 0,
            'featured' => $can['productsView'] ? Product::query()->featured()->count() : 0,
            'lowStock' => $can['productsView'] ? Product::query()->lowStock()->count() : 0,
        ];

        $categoryStats = [
            'total' => $can['categoriesView'] ? Category::query()->count() : 0,
            'roots' => $can['categoriesView'] ? Category::query()->whereNull('parent_id')->count() : 0,
        ];

        $mediaStats = [
            'total' => $can['mediaView'] ? Media::query()->count() : 0,
            'images' => $can['mediaView'] ? Media::query()->where('mime_type', 'like', 'image/%')->count() : 0,
            'videos' => $can['mediaView'] ? Media::query()->where('mime_type', 'like', 'video/%')->count() : 0,
        ];

        $galleryStats = [
            'total' => $can['galleriesView'] ? Gallery::query()->count() : 0,
            'items' => $can['galleriesView'] ? (int) DB::table('gallery_items')->count() : 0,
            'attached' => $can['galleriesView'] ? (int) DB::table('galleryables')->count() : 0,
        ];

        $userStats = [
            'total' => $can['usersView'] ? User::query()->count() : 0,
            'active' => $can['usersView'] ? User::query()->where('is_active', true)->count() : 0,
            'admins' => $can['usersView']
                ? User::query()->whereHas('roles', fn ($query) => $query->whereIn('slug', ['admin', 'superadmin']))->count()
                : 0,
        ];

        $trashStats = [
            'blog' => BlogPost::onlyTrashed()->count(),
            'projects' => Project::onlyTrashed()->count(),
            'products' => Product::onlyTrashed()->count(),
            'categories' => Category::onlyTrashed()->count(),
            'media' => Media::onlyTrashed()->count(),
            'galleries' => Gallery::onlyTrashed()->count(),
        ];
        $trashTotal = array_sum($trashStats);

        $messagesQuery = ContactMessage::query()->visibleToUser($user);
        $messageStats = [
            'total' => $can['messagesView'] ? (clone $messagesQuery)->count() : 0,
            'unread' => $can['messagesView'] ? (clone $messagesQuery)->whereNull('read_at')->count() : 0,
            'urgent' => $can['messagesView']
                ? (clone $messagesQuery)->whereIn('priority', [
                    ContactMessage::PRIORITY_HIGH,
                    ContactMessage::PRIORITY_URGENT,
                ])->count()
                : 0,
            'guest' => $can['messagesView']
                ? (clone $messagesQuery)->where('sender_type', ContactMessage::SENDER_TYPE_GUEST)->count()
                : 0,
        ];

        $appointmentsQuery = Appointment::query()->where('status', Appointment::STATUS_BOOKED);
        if (!$user->isSuperAdmin()) {
            $appointmentsQuery->where('provider_id', $user->id);
        }

        $appointmentsToday = $can['appointmentsView']
            ? (clone $appointmentsQuery)->whereBetween('start_at', [$now->copy()->startOfDay(), $now->copy()->endOfDay()])->count()
            : 0;

        $appointmentsWeek = $can['appointmentsView']
            ? (clone $appointmentsQuery)->whereBetween('start_at', [$now->copy()->startOfDay(), $now->copy()->addDays(6)->endOfDay()])->count()
            : 0;

        $auditStats = [
            'errors' => $can['auditView'] ? AuditLog::query()->where('status', '>=', 400)->count() : 0,
            'today' => $can['auditView']
                ? AuditLog::query()->where('created_at', '>=', $now->copy()->startOfDay())->count()
                : 0,
        ];

        $orderStats = $this->orderStats($can, $now);
        $paymentHealth = $this->paymentHealth($can, $now);
        $siteHealth = $this->siteHealth($can);
        $contentQuality = $this->contentQuality($can);
        $inventoryRisks = $this->inventoryRisks($can);
        $memberStats = $this->memberStats($can, $now);
        $appointmentPerformance = $this->appointmentPerformance($can, $user, $now);
        $supportSla = $this->supportSla($can, $messagesQuery, $now);
        $translationHealth = $this->translationHealth($can);

        $contentTotal = $blogStats['total'] + $projectStats['total'] + $productStats['total'];
        $focusTotal = $messageStats['unread']
            + $messageStats['urgent']
            + $productStats['lowStock']
            + $trashTotal
            + $appointmentsToday
            + $orderStats['requires_action']
            + $paymentHealth['issues']
            + $siteHealth['issues']
            + $contentQuality['risk_total']
            + $inventoryRisks['risk_total']
            + $supportSla['overdue_unread'];

        $kpis = [
            [
                'label' => 'İçerik havuzu',
                'value' => $contentTotal,
                'hint' => "{$blogStats['total']} blog, {$projectStats['total']} proje, {$productStats['total']} ürün",
                'icon' => 'ki-filled ki-element-11',
                'accent' => '#3e97ff',
                'visibility_key' => 'kpi_content_pool',
            ],
            [
                'label' => 'Okunmamış mesaj',
                'value' => $messageStats['unread'],
                'hint' => $messageStats['urgent'] > 0 ? "{$messageStats['urgent']} yüksek öncelik" : 'Mesaj kutusunu takip et',
                'icon' => 'ki-filled ki-messages',
                'accent' => '#f6b100',
                'visibility_key' => 'kpi_unread_messages',
            ],
            [
                'label' => 'Bu hafta randevu',
                'value' => $appointmentsWeek,
                'hint' => $appointmentsToday > 0 ? "Bugün {$appointmentsToday} randevu var" : 'Takvim sakin gözüküyor',
                'icon' => 'ki-filled ki-calendar-8',
                'accent' => '#17c653',
                'visibility_key' => 'kpi_weekly_appointments',
            ],
            [
                'label' => 'Düşük stok',
                'value' => $productStats['lowStock'],
                'hint' => $productStats['active'] > 0 ? "{$productStats['active']} aktif ürün yayınlı" : 'Stok akışına göz at',
                'icon' => 'ki-filled ki-handcart',
                'accent' => '#f1416c',
                'visibility_key' => 'kpi_low_stock',
            ],
            [
                'label' => $can['auditView'] ? 'Sistem uyarısı' : 'Çöp kutusu',
                'value' => $can['auditView'] ? $auditStats['errors'] : $trashTotal,
                'hint' => $can['auditView']
                    ? ($auditStats['today'] > 0 ? "Bugün {$auditStats['today']} log oluştu" : 'Kritik logları takip et')
                    : "{$trashTotal} kayıt geri yüklenebilir",
                'icon' => $can['auditView'] ? 'ki-filled ki-fingerprint-scanning' : 'ki-filled ki-trash',
                'accent' => $can['auditView'] ? '#7239ea' : '#1f2937',
                'visibility_key' => 'kpi_system_alerts',
            ],
        ];

        if ($can['ecommerceOrdersView']) {
            $kpis[] = [
                'label' => '30 günlük ciro',
                'value' => (int) round($orderStats['month_revenue']),
                'hint' => $this->money($orderStats['today_revenue']) . " bugün, {$orderStats['month_count']} sipariş",
                'icon' => 'ki-filled ki-basket',
                'accent' => '#0ea5e9',
                'visibility_key' => 'kpi_monthly_revenue',
            ];
        }

        if ($can['sitePaymentsView']) {
            $kpis[] = [
                'label' => 'Ödeme uyarısı',
                'value' => $paymentHealth['issues'],
                'hint' => $paymentHealth['default_label'] ?: 'Varsayılan ödeme sağlayıcısını kontrol et',
                'icon' => 'ki-filled ki-credit-cart',
                'accent' => '#f97316',
                'visibility_key' => 'kpi_payment_health',
            ];
        }

        if ($can['membersView']) {
            $kpis[] = [
                'label' => 'Aktif üyeler',
                'value' => $memberStats['active'],
                'hint' => "{$memberStats['new_week']} yeni üye, {$memberStats['suspended']} askıda",
                'icon' => 'ki-filled ki-users',
                'accent' => '#14b8a6',
                'visibility_key' => 'kpi_member_health',
            ];
        }

        $quickActions = array_values(array_filter([
            $can['ecommerceOrdersCreate'] ? [
                'label' => 'Yeni sipariş',
                'url' => route('admin.ecommerce.orders.create'),
                'style' => 'kt-btn-primary',
                'icon' => 'ki-filled ki-basket',
            ] : null,
            $can['messagesView'] ? [
                'label' => 'Mesaj kutusu',
                'url' => route('admin.messages.index'),
                'style' => $can['ecommerceOrdersCreate'] ? 'kt-btn-light' : 'kt-btn-primary',
                'icon' => 'ki-filled ki-messages',
            ] : null,
            $can['appointmentsView'] ? [
                'label' => 'Randevu takvimi',
                'url' => route('admin.appointments.calendar'),
                'style' => 'kt-btn-light',
                'icon' => 'ki-filled ki-calendar-8',
            ] : null,
            $can['blogCreate'] ? [
                'label' => 'Yeni blog',
                'url' => route('admin.blog.create'),
                'style' => 'kt-btn-light',
                'icon' => 'ki-filled ki-book',
            ] : null,
            $can['projectsCreate'] ? [
                'label' => 'Yeni proje',
                'url' => route('admin.projects.create'),
                'style' => 'kt-btn-light',
                'icon' => 'ki-filled ki-briefcase',
            ] : null,
            $can['productsCreate'] ? [
                'label' => 'Yeni ürün',
                'url' => route('admin.products.create'),
                'style' => 'kt-btn-light',
                'icon' => 'ki-filled ki-handcart',
            ] : null,
            $can['siteSettingsView'] ? [
                'label' => 'Site sağlığı',
                'url' => route('admin.site.settings.edit'),
                'style' => 'kt-btn-light',
                'icon' => 'ki-filled ki-setting-2',
            ] : null,
            $can['trashView'] ? [
                'label' => 'Silinenler',
                'url' => route('admin.trash.index'),
                'style' => 'kt-btn-outline',
                'icon' => 'ki-filled ki-trash',
            ] : null,
        ]));

        $focusItems = array_values(array_filter([
            $messageStats['unread'] > 0 ? [
                'label' => 'Okunmamış mesajlar',
                'count' => $messageStats['unread'],
                'hint' => 'Mesaj kutusu yeni geri dönüş bekliyor.',
                'url' => route('admin.messages.index'),
                'icon' => 'ki-filled ki-messages',
                'accent' => '#f6b100',
            ] : null,
            $messageStats['urgent'] > 0 ? [
                'label' => 'Yüksek öncelikli talepler',
                'count' => $messageStats['urgent'],
                'hint' => 'Acil veya yüksek talepleri önde tut.',
                'url' => route('admin.messages.index'),
                'icon' => 'ki-filled ki-notification-status',
                'accent' => '#f1416c',
            ] : null,
            $appointmentsToday > 0 && $can['appointmentsView'] ? [
                'label' => 'Bugünkü randevular',
                'count' => $appointmentsToday,
                'hint' => 'Takvimde bugün için planlanan görüşmeleri kontrol et.',
                'url' => route('admin.appointments.calendar'),
                'icon' => 'ki-filled ki-calendar-8',
                'accent' => '#17c653',
            ] : null,
            $productStats['lowStock'] > 0 && $can['productsView'] ? [
                'label' => 'Düşük stoklu ürünler',
                'count' => $productStats['lowStock'],
                'hint' => 'Stok yenileme veya yayın kararlarını gözden geçir.',
                'url' => route('admin.products.index'),
                'icon' => 'ki-filled ki-handcart',
                'accent' => '#3e97ff',
            ] : null,
            $trashTotal > 0 && $can['trashView'] ? [
                'label' => 'Çöp kutusunda bekleyen kayıtlar',
                'count' => $trashTotal,
                'hint' => 'Geri yükleme veya kalıcı silme kararı bekliyor.',
                'url' => route('admin.trash.index'),
                'icon' => 'ki-filled ki-trash',
                'accent' => '#1f2937',
            ] : null,
            $orderStats['requires_action'] > 0 && $can['ecommerceOrdersView'] ? [
                'label' => 'Sipariş aksiyon kuyruğu',
                'count' => $orderStats['requires_action'],
                'hint' => 'Ödeme, onay veya kargo adımı bekleyen siparişleri tamamla.',
                'url' => route('admin.ecommerce.orders.index'),
                'icon' => 'ki-filled ki-basket',
                'accent' => '#0ea5e9',
            ] : null,
            $paymentHealth['issues'] > 0 && $can['sitePaymentsView'] ? [
                'label' => 'Ödeme yapılandırma uyarısı',
                'count' => $paymentHealth['issues'],
                'hint' => 'Varsayılan sağlayıcı, eksik bilgi veya başarısız ödeme hareketlerini kontrol et.',
                'url' => route('admin.site.payments.index'),
                'icon' => 'ki-filled ki-credit-cart',
                'accent' => '#f97316',
            ] : null,
            $siteHealth['issues'] > 0 && $can['siteSettingsView'] ? [
                'label' => 'Site sağlığı uyarısı',
                'count' => $siteHealth['issues'],
                'hint' => 'SEO dosyaları, SMTP veya bakım modu ayarlarını gözden geçir.',
                'url' => route('admin.site.settings.edit'),
                'icon' => 'ki-filled ki-setting-2',
                'accent' => '#7239ea',
            ] : null,
            $supportSla['overdue_unread'] > 0 && $can['messagesView'] ? [
                'label' => 'Geciken mesajlar',
                'count' => $supportSla['overdue_unread'],
                'hint' => '24 saati aşan okunmamış talepler öncelik bekliyor.',
                'url' => route('admin.messages.index'),
                'icon' => 'ki-filled ki-timer',
                'accent' => '#f1416c',
            ] : null,
        ]));

        $moduleCards = array_values(array_filter([
            $can['blogView'] ? [
                'title' => 'Blog',
                'value' => $blogStats['total'],
                'hint' => "{$blogStats['published']} yayında, {$blogStats['draft']} taslak",
                'route' => route('admin.blog.index'),
                'action_label' => $can['blogCreate'] ? 'Yeni yazı' : null,
                'action_url' => $can['blogCreate'] ? route('admin.blog.create') : null,
                'icon' => 'ki-filled ki-book',
                'accent' => '#3e97ff',
                'visibility_key' => 'module_blog',
            ] : null,
            $can['projectsView'] ? [
                'title' => 'Projeler',
                'value' => $projectStats['total'],
                'hint' => "{$projectStats['public']} sitede görünebilir, {$projectStats['featured']} vitrin",
                'route' => route('admin.projects.index'),
                'action_label' => $can['projectsCreate'] ? 'Yeni proje' : null,
                'action_url' => $can['projectsCreate'] ? route('admin.projects.create') : null,
                'icon' => 'ki-filled ki-briefcase',
                'accent' => '#17c653',
                'visibility_key' => 'module_projects',
            ] : null,
            $can['productsView'] ? [
                'title' => 'Ürünler',
                'value' => $productStats['total'],
                'hint' => "{$productStats['lowStock']} düşük stok, {$productStats['featured']} vitrin",
                'route' => route('admin.products.index'),
                'action_label' => $can['productsCreate'] ? 'Yeni ürün' : null,
                'action_url' => $can['productsCreate'] ? route('admin.products.create') : null,
                'icon' => 'ki-filled ki-handcart',
                'accent' => '#f1416c',
                'visibility_key' => 'module_products',
            ] : null,
            $can['ecommerceOrdersView'] ? [
                'title' => 'E-Ticaret',
                'value' => $orderStats['month_count'],
                'hint' => $this->money($orderStats['month_revenue']) . ", {$orderStats['requires_action']} aksiyon bekliyor",
                'route' => route('admin.ecommerce.orders.index'),
                'action_label' => $can['ecommerceOrdersCreate'] ? 'Yeni sipariş' : 'Siparişler',
                'action_url' => $can['ecommerceOrdersCreate'] ? route('admin.ecommerce.orders.create') : route('admin.ecommerce.orders.index'),
                'icon' => 'ki-filled ki-basket',
                'accent' => '#0ea5e9',
                'visibility_key' => 'module_ecommerce',
            ] : null,
            $can['messagesView'] ? [
                'title' => 'Mesajlar',
                'value' => $messageStats['total'],
                'hint' => "{$messageStats['unread']} okunmamış, {$messageStats['guest']} ziyaretçi",
                'route' => route('admin.messages.index'),
                'action_label' => 'Kutuyu aç',
                'action_url' => route('admin.messages.index'),
                'icon' => 'ki-filled ki-messages',
                'accent' => '#f6b100',
                'visibility_key' => 'module_messages',
            ] : null,
            $can['membersView'] ? [
                'title' => 'Üyelikler',
                'value' => $memberStats['total'],
                'hint' => "{$memberStats['active']} aktif, {$memberStats['new_week']} yeni kayıt",
                'route' => route('admin.members.index'),
                'action_label' => 'Üyeleri aç',
                'action_url' => route('admin.members.index'),
                'icon' => 'ki-filled ki-users',
                'accent' => '#14b8a6',
                'visibility_key' => 'module_members',
            ] : null,
            $can['appointmentsView'] ? [
                'title' => 'Randevular',
                'value' => $appointmentsWeek,
                'hint' => $appointmentsToday > 0 ? "Bugün {$appointmentsToday} görüşme var" : 'Haftalık plan sakin',
                'route' => route('admin.appointments.calendar'),
                'action_label' => 'Takvimi aç',
                'action_url' => route('admin.appointments.calendar'),
                'icon' => 'ki-filled ki-calendar-8',
                'accent' => '#7239ea',
                'visibility_key' => 'module_appointments',
            ] : null,
            $can['mediaView'] ? [
                'title' => 'Medya',
                'value' => $mediaStats['total'],
                'hint' => "{$mediaStats['images']} görsel, {$mediaStats['videos']} video",
                'route' => route('admin.media.index'),
                'action_label' => 'Kütüphane',
                'action_url' => route('admin.media.index'),
                'icon' => 'ki-filled ki-screen',
                'accent' => '#0ea5e9',
                'visibility_key' => 'module_media',
            ] : null,
            $can['galleriesView'] ? [
                'title' => 'Galeriler',
                'value' => $galleryStats['total'],
                'hint' => "{$galleryStats['items']} öge, {$galleryStats['attached']} bağlı kullanım",
                'route' => route('admin.galleries.index'),
                'action_label' => $can['galleriesCreate'] ? 'Yeni galeri' : null,
                'action_url' => $can['galleriesCreate'] ? route('admin.galleries.create') : null,
                'icon' => 'ki-filled ki-picture',
                'accent' => '#14b8a6',
                'visibility_key' => 'module_galleries',
            ] : null,
            $can['categoriesView'] ? [
                'title' => 'Kategoriler',
                'value' => $categoryStats['total'],
                'hint' => "{$categoryStats['roots']} kök kategori",
                'route' => route('admin.categories.index'),
                'action_label' => $can['categoriesCreate'] ? 'Yeni kategori' : null,
                'action_url' => $can['categoriesCreate'] ? route('admin.categories.create') : null,
                'icon' => 'ki-filled ki-document',
                'accent' => '#ef4444',
                'visibility_key' => 'module_categories',
            ] : null,
            $can['usersView'] ? [
                'title' => 'Kullanıcılar',
                'value' => $userStats['total'],
                'hint' => "{$userStats['active']} aktif, {$userStats['admins']} admin",
                'route' => route('admin.users.index'),
                'action_label' => 'Listeyi aç',
                'action_url' => route('admin.users.index'),
                'icon' => 'ki-filled ki-profile-circle',
                'accent' => '#8b5cf6',
                'visibility_key' => 'module_users',
            ] : null,
            $can['trashView'] ? [
                'title' => 'Silinenler',
                'value' => $trashTotal,
                'hint' => "{$trashStats['media']} medya, {$trashStats['products']} ürün, {$trashStats['blog']} blog",
                'route' => route('admin.trash.index'),
                'action_label' => 'Çöp kutusu',
                'action_url' => route('admin.trash.index'),
                'icon' => 'ki-filled ki-trash',
                'accent' => '#334155',
                'visibility_key' => 'module_trash',
            ] : null,
            $can['auditView'] ? [
                'title' => 'Loglar',
                'value' => $auditStats['errors'],
                'hint' => "{$auditStats['today']} bugün, sistem akışlarını kontrol et",
                'route' => route('admin.audit-logs.index'),
                'action_label' => 'Logları aç',
                'action_url' => route('admin.audit-logs.index'),
                'icon' => 'ki-filled ki-fingerprint-scanning',
                'accent' => '#a855f7',
                'visibility_key' => 'module_audit',
            ] : null,
            $can['siteSettingsView'] ? [
                'title' => 'Site Sağlığı',
                'value' => $siteHealth['score'],
                'hint' => "{$siteHealth['issues']} uyarı, SEO ve bildirim ayarlarını izle",
                'route' => route('admin.site.settings.edit'),
                'action_label' => 'Ayarları aç',
                'action_url' => route('admin.site.settings.edit'),
                'icon' => 'ki-filled ki-setting-2',
                'accent' => '#64748b',
                'visibility_key' => 'module_site_health',
            ] : null,
            $can['sitePaymentsView'] ? [
                'title' => 'Ödemeler',
                'value' => $paymentHealth['active'],
                'hint' => "{$paymentHealth['incomplete']} eksik yapılandırma, {$paymentHealth['failed_recent']} başarısız hareket",
                'route' => route('admin.site.payments.index'),
                'action_label' => 'Ödemeleri aç',
                'action_url' => route('admin.site.payments.index'),
                'icon' => 'ki-filled ki-credit-cart',
                'accent' => '#f97316',
                'visibility_key' => 'module_payments',
            ] : null,
        ]));

        $monthlyActivity = [
            'labels' => $monthLabels,
            'series' => array_values(array_filter([
                $can['blogView'] ? [
                    'name' => 'Blog',
                    'data' => $this->seriesByMonth(BlogPost::query(), $monthStart, $monthKeys),
                ] : null,
                $can['projectsView'] ? [
                    'name' => 'Projeler',
                    'data' => $this->seriesByMonth(Project::query(), $monthStart, $monthKeys),
                ] : null,
                $can['productsView'] ? [
                    'name' => 'Ürünler',
                    'data' => $this->seriesByMonth(Product::query(), $monthStart, $monthKeys),
                ] : null,
                $can['ecommerceOrdersView'] ? [
                    'name' => 'Sipariş',
                    'data' => $this->seriesByMonth(EcommerceOrder::query(), $monthStart, $monthKeys, 'ordered_at'),
                ] : null,
                $can['membersView'] ? [
                    'name' => 'Üyelik',
                    'data' => $this->seriesByMonth(Member::query(), $monthStart, $monthKeys),
                ] : null,
                $can['mediaView'] ? [
                    'name' => 'Medya',
                    'data' => $this->seriesByMonth(Media::query(), $monthStart, $monthKeys),
                ] : null,
                $can['messagesView'] ? [
                    'name' => 'Mesajlar',
                    'data' => $this->seriesByMonth(ContactMessage::query()->visibleToUser($user), $monthStart, $monthKeys),
                ] : null,
            ])),
        ];

        $actionSeries = array_values(array_filter([
            $messageStats['unread'] > 0 ? ['label' => 'Okunmamış', 'value' => $messageStats['unread']] : null,
            $messageStats['urgent'] > 0 ? ['label' => 'Yüksek öncelik', 'value' => $messageStats['urgent']] : null,
            $appointmentsToday > 0 ? ['label' => 'Bugünkü randevu', 'value' => $appointmentsToday] : null,
            $productStats['lowStock'] > 0 ? ['label' => 'Düşük stok', 'value' => $productStats['lowStock']] : null,
            $orderStats['requires_action'] > 0 ? ['label' => 'Sipariş aksiyonu', 'value' => $orderStats['requires_action']] : null,
            $paymentHealth['issues'] > 0 ? ['label' => 'Ödeme uyarısı', 'value' => $paymentHealth['issues']] : null,
            $siteHealth['issues'] > 0 ? ['label' => 'Site sağlığı', 'value' => $siteHealth['issues']] : null,
            $contentQuality['risk_total'] > 0 ? ['label' => 'İçerik riski', 'value' => $contentQuality['risk_total']] : null,
            $supportSla['overdue_unread'] > 0 ? ['label' => 'Geciken mesaj', 'value' => $supportSla['overdue_unread']] : null,
            $trashTotal > 0 ? ['label' => 'Çöp kutusu', 'value' => $trashTotal] : null,
        ]));
        if (count($actionSeries) === 0) {
            $actionSeries[] = ['label' => 'Takip bekleyen is yok', 'value' => 1];
        }

        $actionChart = [
            'labels' => array_column($actionSeries, 'label'),
            'series' => array_map(fn ($item) => (int) $item['value'], $actionSeries),
            'total' => $focusTotal,
        ];

        $scheduleChart = [
            'labels' => $dayLabels,
            'series' => $can['appointmentsView']
                ? $this->seriesByDay((clone $appointmentsQuery), $dayStart, $dayKeys, 'start_at')
                : array_fill(0, count($dayLabels), 0),
        ];

        $recentMessages = $can['messagesView']
            ? (clone $messagesQuery)
                ->with(['recipient:id,name', 'member:id,name,surname'])
                ->latest('created_at')
                ->limit(5)
                ->get()
                ->map(function (ContactMessage $message) {
                    $priorityLabel = ContactMessage::priorityLabel($message->priority);
                    $priorityBadge = ContactMessage::priorityBadgeClass($message->priority);

                    return [
                        'id' => 'message-' . $message->id,
                        'start' => $message->created_at?->toIso8601String(),
                        'subject' => $message->subject,
                        'title' => $message->subject,
                        'sender' => $message->sender_full_name ?: 'Bilinmeyen',
                        'time' => $message->created_at?->diffForHumans(),
                        'subtitle' => trim(($message->sender_full_name ?: 'Bilinmeyen') . ' | ' . ($message->created_at?->diffForHumans() ?: '-')),
                        'description' => $message->recipient_display_name,
                        'priority_label' => $priorityLabel,
                        'priority_badge' => $priorityBadge,
                        'status_badge' => $message->isRead() ? 'kt-badge kt-badge-sm kt-badge-light-success' : 'kt-badge kt-badge-sm kt-badge-light-warning',
                        'status_label' => $message->isRead() ? 'Okundu' : 'Okunmadı',
                        'status' => $priorityLabel,
                        'badgeClass' => $priorityBadge,
                        'variant' => match ($message->priority) {
                            ContactMessage::PRIORITY_URGENT => 'danger',
                            ContactMessage::PRIORITY_HIGH => 'warning',
                            ContactMessage::PRIORITY_LOW => 'default',
                            default => 'primary',
                        },
                        'icon' => 'ki-filled ki-messages',
                        'url' => route('admin.messages.show', $message),
                    ];
                })
                ->values()
            : collect();

        $upcomingAppointments = $can['appointmentsView']
            ? (clone $appointmentsQuery)
                ->with(['member:id,name,surname', 'provider:id,name'])
                ->where('start_at', '>=', $now->copy()->startOfDay())
                ->orderBy('start_at')
                ->limit(5)
                ->get()
                ->map(function (Appointment $appointment) use ($user) {
                    $memberName = trim(($appointment->member?->name ?? '') . ' ' . ($appointment->member?->surname ?? ''));

                    return [
                        'id' => 'appointment-' . $appointment->id,
                        'start' => $appointment->start_at?->toIso8601String(),
                        'end' => $appointment->end_at?->toIso8601String(),
                        'title' => $memberName !== '' ? $memberName : 'Randevu',
                        'provider' => $user->isSuperAdmin() ? ($appointment->provider?->name ?: '-') : null,
                        'time' => $appointment->start_at?->timezone('Europe/Istanbul')->format('d M H:i'),
                        'subtitle' => trim(($appointment->start_at?->timezone('Europe/Istanbul')->format('d M H:i') ?: '-') . ($user->isSuperAdmin() ? ' | ' . ($appointment->provider?->name ?: '-') : '')),
                        'description' => $appointment->end_at?->timezone('Europe/Istanbul')->format('d M H:i'),
                        'status' => 'Randevu',
                        'badgeClass' => 'kt-badge kt-badge-sm kt-badge-light-primary',
                        'variant' => 'success',
                        'icon' => 'ki-filled ki-calendar-8',
                        'url' => route('admin.appointments.calendar'),
                    ];
                })
                ->values()
            : collect();

        $recentContent = $this->recentContent($can)
            ->sortByDesc('updated_at')
            ->take(6)
            ->values()
            ->map(function (array $item) {
                $item['updated_label'] = optional($item['updated_at'])->diffForHumans();
                $item['start'] = optional($item['updated_at'])->toIso8601String();
                $item['subtitle'] = trim(($item['type'] ?? 'İçerik') . ' | ' . ($item['updated_label'] ?: '-'));
                $item['description'] = $item['updated_label'];
                $item['status'] = $item['meta'] ?? null;
                $item['badgeClass'] = $item['badge'] ?? 'kt-badge kt-badge-sm kt-badge-light';
                $item['variant'] = 'primary';
                $item['icon'] = 'ki-filled ki-document';

                return $item;
            });

        $healthCards = $this->healthCards(
            $can,
            $orderStats,
            $paymentHealth,
            $siteHealth,
            $contentQuality,
            $inventoryRisks,
            $memberStats,
            $appointmentPerformance,
            $supportSla,
            $translationHealth
        );
        $riskGroups = $this->riskGroups($can, $paymentHealth, $siteHealth, $contentQuality, $inventoryRisks, $supportSla, $translationHealth);
        $recentOrders = $this->recentOrders($can);
        $commercePipeline = $this->commercePipeline($orderStats);

        $dashboardSections = $this->dashboardSectionDefinitions($can);
        $dashboardSectionVisibility = $this->resolveDashboardSectionVisibility($user, $dashboardSections);
        $dashboardSectionOrder = $this->resolveDashboardSectionOrder($user, $dashboardSections);
        $dashboardSectionOrderIndex = $this->dashboardSectionOrderIndex($dashboardSectionOrder);
        $dashboardFlowOrder = min(array_map(
            fn (string $key) => $dashboardSectionOrderIndex[$key] ?? PHP_INT_MAX,
            ['recent_messages', 'upcoming_appointments', 'recent_content']
        ));

        $kpis = $this->sortDashboardItemsByOrder($kpis, $dashboardSectionOrder);
        $moduleCards = $this->sortDashboardItemsByOrder($moduleCards, $dashboardSectionOrder);
        $hasVisibleDashboardSection = collect($dashboardSectionVisibility)->contains(fn (bool $visible) => $visible === true);

        $recentAuditIssues = $can['auditView']
            ? AuditLog::query()
                ->where('status', '>=', 400)
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn (AuditLog $log) => [
                    'id' => 'audit-' . $log->id,
                    'start' => $log->created_at?->toIso8601String(),
                    'status' => (int) $log->status,
                    'route' => $log->route ?: ($log->uri ?: 'request'),
                    'title' => $log->route ?: ($log->uri ?: 'request'),
                    'method' => strtoupper((string) $log->method),
                    'time' => $log->created_at?->diffForHumans(),
                    'subtitle' => trim(strtoupper((string) $log->method) . ' | ' . ($log->created_at?->diffForHumans() ?: '-')),
                    'description' => $log->uri ?: null,
                    'badgeClass' => 'kt-badge kt-badge-sm ' . ((int) $log->status >= 500 ? 'kt-badge-danger' : 'kt-badge-warning'),
                    'variant' => (int) $log->status >= 500 ? 'danger' : 'warning',
                    'icon' => 'ki-filled ki-fingerprint-scanning',
                    'url' => route('admin.audit-logs.show', $log),
                ])
                ->values()
            : collect();

        return view('admin.pages.dash.index', [
            'pageTitle' => 'Kontrol Paneli',
            'pageDescription' => 'Yönetim merkezi',
            'greeting' => $this->greetingForHour((int) $now->format('H')),
            'heroSummary' => $this->heroSummary(
                $user,
                $appointmentsToday,
                $messageStats['unread'],
                $productStats['lowStock'],
                $trashTotal,
                $orderStats['requires_action'],
                $siteHealth['issues']
            ),
            'quickActions' => $quickActions,
            'focusItems' => $focusItems,
            'kpis' => $kpis,
            'moduleCards' => $moduleCards,
            'healthCards' => $healthCards,
            'riskGroups' => $riskGroups,
            'recentOrders' => $recentOrders,
            'commercePipeline' => $commercePipeline,
            'orderStats' => $orderStats,
            'paymentHealth' => $paymentHealth,
            'siteHealth' => $siteHealth,
            'contentQuality' => $contentQuality,
            'inventoryRisks' => $inventoryRisks,
            'memberStats' => $memberStats,
            'appointmentPerformance' => $appointmentPerformance,
            'supportSla' => $supportSla,
            'translationHealth' => $translationHealth,
            'monthlyActivity' => $monthlyActivity,
            'actionChart' => $actionChart,
            'scheduleChart' => $scheduleChart,
            'recentMessages' => $recentMessages,
            'upcomingAppointments' => $upcomingAppointments,
            'recentContent' => $recentContent,
            'recentAuditIssues' => $recentAuditIssues,
            'canAudit' => $can['auditView'],
            'canAppointments' => $can['appointmentsView'],
            'canEcommerce' => $can['ecommerceOrdersView'],
            'focusTotal' => $focusTotal,
            'nowLabel' => $now->format('d M Y, H:i'),
            'dashboardSections' => $dashboardSections,
            'dashboardSectionVisibility' => $dashboardSectionVisibility,
            'dashboardSectionOrderIndex' => $dashboardSectionOrderIndex,
            'dashboardFlowOrder' => $dashboardFlowOrder,
            'hasVisibleDashboardSection' => $hasVisibleDashboardSection,
        ]);
    }

    public function manage(): View
    {
        /** @var User $user */
        $user = auth()->user();

        $capabilities = [
            'messagesView' => $user->canAccessAdmin(),
            'appointmentsView' => $this->can($user, 'appointments.view'),
            'auditView' => $this->can($user, 'audit-logs.view'),
            'blogView' => $this->can($user, 'blog.view'),
            'projectsView' => $this->can($user, 'projects.view'),
            'productsView' => $this->can($user, 'products.view'),
            'mediaView' => $this->can($user, 'media.view'),
            'galleriesView' => $this->can($user, 'galleries.view'),
            'categoriesView' => $this->can($user, 'categories.view'),
            'usersView' => $this->can($user, 'users.view'),
            'trashView' => $this->can($user, 'trash.view'),
            'ecommerceOrdersView' => $this->can($user, 'ecommerce_orders.view'),
            'membersView' => $this->can($user, 'members.view'),
            'sitePaymentsView' => $this->can($user, 'site_payments.view'),
            'siteSettingsView' => $this->can($user, 'site_settings.view'),
            'siteLanguagesView' => $this->can($user, 'site_languages.view'),
            'sitePagesView' => $this->can($user, 'site_pages.view'),
        ];

        $dashboardSections = $this->dashboardSectionDefinitions($capabilities);
        $dashboardSectionVisibility = $this->resolveDashboardSectionVisibility($user, $dashboardSections);
        $dashboardSectionOrder = $this->resolveDashboardSectionOrder($user, $dashboardSections);
        $dashboardSectionOrderIndex = $this->dashboardSectionOrderIndex($dashboardSectionOrder);
        $orderedDashboardSections = $this->dashboardSectionsForManagement(
            $dashboardSections,
            $dashboardSectionVisibility,
            $dashboardSectionOrderIndex
        );

        $groupedSections = $orderedDashboardSections->groupBy('group');

        return view('admin.pages.dash.manage', [
            'pageTitle' => 'Kontrol Paneli Yönetimi',
            'pageDescription' => 'Kontrol paneli yönetimi',
            'dashboardSectionGroups' => $groupedSections,
            'orderedDashboardSections' => $orderedDashboardSections,
            'dashboardSectionVisibility' => $dashboardSectionVisibility,
            'activeSectionCount' => collect($dashboardSectionVisibility)->filter()->count(),
            'availableSectionCount' => count(DashboardSectionRegistry::availableKeys($dashboardSections)),
        ]);
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $capabilities = [
            'messagesView' => $user->canAccessAdmin(),
            'appointmentsView' => $this->can($user, 'appointments.view'),
            'auditView' => $this->can($user, 'audit-logs.view'),
            'blogView' => $this->can($user, 'blog.view'),
            'projectsView' => $this->can($user, 'projects.view'),
            'productsView' => $this->can($user, 'products.view'),
            'mediaView' => $this->can($user, 'media.view'),
            'galleriesView' => $this->can($user, 'galleries.view'),
            'categoriesView' => $this->can($user, 'categories.view'),
            'usersView' => $this->can($user, 'users.view'),
            'trashView' => $this->can($user, 'trash.view'),
            'ecommerceOrdersView' => $this->can($user, 'ecommerce_orders.view'),
            'membersView' => $this->can($user, 'members.view'),
            'sitePaymentsView' => $this->can($user, 'site_payments.view'),
            'siteSettingsView' => $this->can($user, 'site_settings.view'),
            'siteLanguagesView' => $this->can($user, 'site_languages.view'),
            'sitePagesView' => $this->can($user, 'site_pages.view'),
        ];

        $dashboardSections = $this->dashboardSectionDefinitions($capabilities);
        $defaults = DashboardSectionRegistry::defaults($dashboardSections);
        $availableKeys = DashboardSectionRegistry::availableKeys($dashboardSections);
        $defaultOrder = $availableKeys;

        $validated = $request->validate([
            'action' => ['nullable', 'string', 'in:save,reset'],
            'visible_sections' => ['nullable', 'array'],
            'visible_sections.*' => ['string'],
            'section_order' => ['nullable', 'array'],
            'section_order.*' => ['string'],
        ]);

        if (($validated['action'] ?? 'save') === 'reset') {
            $user->dashboardPreference()->delete();

            return redirect()
                ->route('admin.dashboard.manage')
                ->with('success', 'Kontrol paneli görünümü varsayılan ayarlara döndürüldü.');
        }

        $selectedKeys = collect($validated['visible_sections'] ?? [])
            ->map(fn ($key) => (string) $key)
            ->filter(fn ($key) => in_array($key, $availableKeys, true))
            ->unique()
            ->values()
            ->all();

        $visibleSections = [];
        foreach ($availableKeys as $key) {
            $visibleSections[$key] = in_array($key, $selectedKeys, true);
        }

        $submittedOrder = collect($validated['section_order'] ?? [])
            ->map(fn ($key) => (string) $key)
            ->filter(fn ($key) => in_array($key, $availableKeys, true))
            ->unique()
            ->values();

        $sectionOrder = $submittedOrder
            ->merge($availableKeys)
            ->unique()
            ->values()
            ->all();

        if ($visibleSections === $defaults && $sectionOrder === $defaultOrder) {
            $user->dashboardPreference()->delete();
        } else {
            $user->dashboardPreference()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'visible_sections' => $visibleSections,
                    'section_order' => $sectionOrder,
                ]
            );
        }

        return redirect()
            ->route('admin.dashboard.manage')
            ->with('success', 'Kontrol paneli görünürlüğü ve sırası güncellendi.');
    }

    private function can(?User $user, string $permission): bool
    {
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'canAccess')) {
            return (bool) $user->canAccess($permission);
        }

        return (bool) $user->can($permission);
    }

    private function greetingForHour(int $hour): string
    {
        return match (true) {
            $hour < 12 => 'Günaydın',
            $hour < 18 => 'İyi günler',
            default => 'İyi akşamlar',
        };
    }

    private function heroSummary(
        User $user,
        int $appointmentsToday,
        int $unreadMessages,
        int $lowStock,
        int $trashTotal,
        int $orderActions = 0,
        int $siteIssues = 0
    ): string
    {
        $parts = [];

        if ($appointmentsToday > 0) {
            $parts[] = "bugün {$appointmentsToday} randevu";
        }

        if ($unreadMessages > 0) {
            $parts[] = "{$unreadMessages} okunmamış mesaj";
        }

        if ($lowStock > 0) {
            $parts[] = "{$lowStock} düşük stok alarmı";
        }

        if ($orderActions > 0) {
            $parts[] = "{$orderActions} sipariş aksiyonu";
        }

        if ($siteIssues > 0) {
            $parts[] = "{$siteIssues} site sağlığı uyarısı";
        }

        if ($trashTotal > 0) {
            $parts[] = "{$trashTotal} silinmiş kayıt";
        }

        if (count($parts) === 0) {
            return "{$user->name}, panel sakin görünüyor. Hemen yönetmek istediğin modülü açıp günlük akışı hızlandırabilirsin.";
        }

        return "{$user->name}, " . implode(', ', $parts) . ' seni bekliyor.';
    }

    private function orderStats(array $can, Carbon $now): array
    {
        if (!($can['ecommerceOrdersView'] ?? false)) {
            return [
                'total' => 0,
                'today_count' => 0,
                'week_count' => 0,
                'month_count' => 0,
                'today_revenue' => 0.0,
                'month_revenue' => 0.0,
                'pending' => 0,
                'awaiting_payment' => 0,
                'failed_payment' => 0,
                'preparing' => 0,
                'refunds' => 0,
                'requires_action' => 0,
            ];
        }

        $todayStart = $now->copy()->startOfDay();
        $weekStart = $now->copy()->subDays(6)->startOfDay();
        $monthStart = $now->copy()->subDays(29)->startOfDay();
        $revenueStatuses = [EcommerceOrder::PAYMENT_PAID, EcommerceOrder::PAYMENT_PARTIAL, EcommerceOrder::PAYMENT_PARTIALLY_REFUNDED];

        $pending = EcommerceOrder::query()
            ->whereIn('status', [EcommerceOrder::STATUS_PENDING, EcommerceOrder::STATUS_CONFIRMED])
            ->count();
        $awaitingPayment = EcommerceOrder::query()
            ->whereIn('payment_status', [EcommerceOrder::PAYMENT_UNPAID, EcommerceOrder::PAYMENT_AWAITING, EcommerceOrder::PAYMENT_AUTHORIZED])
            ->count();
        $failedPayment = EcommerceOrder::query()
            ->where('payment_status', EcommerceOrder::PAYMENT_FAILED)
            ->count();
        $preparing = EcommerceOrder::query()
            ->whereNotIn('status', [EcommerceOrder::STATUS_CANCELLED, EcommerceOrder::STATUS_COMPLETED, EcommerceOrder::STATUS_REFUNDED])
            ->whereIn('fulfillment_status', [
                EcommerceOrder::FULFILLMENT_UNFULFILLED,
                EcommerceOrder::FULFILLMENT_PREPARING,
                EcommerceOrder::FULFILLMENT_PARTIAL,
            ])
            ->count();
        $refunds = EcommerceOrder::query()
            ->where(function ($query) {
                $query
                    ->where('status', EcommerceOrder::STATUS_REFUNDED)
                    ->orWhereIn('payment_status', [
                        EcommerceOrder::PAYMENT_PARTIALLY_REFUNDED,
                        EcommerceOrder::PAYMENT_REFUNDED,
                    ])
                    ->orWhere('fulfillment_status', EcommerceOrder::FULFILLMENT_RETURNED);
            })
            ->count();

        return [
            'total' => EcommerceOrder::query()->count(),
            'today_count' => EcommerceOrder::query()->where('ordered_at', '>=', $todayStart)->count(),
            'week_count' => EcommerceOrder::query()->where('ordered_at', '>=', $weekStart)->count(),
            'month_count' => EcommerceOrder::query()->where('ordered_at', '>=', $monthStart)->count(),
            'today_revenue' => (float) EcommerceOrder::query()
                ->where('ordered_at', '>=', $todayStart)
                ->whereIn('payment_status', $revenueStatuses)
                ->whereNotIn('status', [EcommerceOrder::STATUS_CANCELLED, EcommerceOrder::STATUS_REFUNDED])
                ->sum('grand_total'),
            'month_revenue' => (float) EcommerceOrder::query()
                ->where('ordered_at', '>=', $monthStart)
                ->whereIn('payment_status', $revenueStatuses)
                ->whereNotIn('status', [EcommerceOrder::STATUS_CANCELLED, EcommerceOrder::STATUS_REFUNDED])
                ->sum('grand_total'),
            'pending' => $pending,
            'awaiting_payment' => $awaitingPayment,
            'failed_payment' => $failedPayment,
            'preparing' => $preparing,
            'refunds' => $refunds,
            'requires_action' => $pending + $awaitingPayment + $failedPayment + $preparing + $refunds,
        ];
    }

    private function paymentHealth(array $can, Carbon $now): array
    {
        if (!($can['sitePaymentsView'] ?? false)) {
            return [
                'total' => 0,
                'active' => 0,
                'live_active' => 0,
                'incomplete' => 0,
                'default_label' => null,
                'default_missing' => false,
                'failed_recent' => 0,
                'pending_recent' => 0,
                'issues' => 0,
            ];
        }

        $integrations = PaymentIntegration::query()->orderByDesc('is_default')->get();
        $default = $integrations->first(fn (PaymentIntegration $integration) => $integration->is_default);
        $incomplete = $integrations->filter(fn (PaymentIntegration $integration) => !$integration->hasCompleteRequiredCredentials())->count();
        $failedRecent = EcommerceOrderTransaction::query()
            ->where('status', EcommerceOrderTransaction::STATUS_FAILED)
            ->where('created_at', '>=', $now->copy()->subDays(7)->startOfDay())
            ->count();
        $pendingRecent = EcommerceOrderTransaction::query()
            ->where('status', EcommerceOrderTransaction::STATUS_PENDING)
            ->where('created_at', '>=', $now->copy()->subDays(7)->startOfDay())
            ->count();

        return [
            'total' => $integrations->count(),
            'active' => $integrations->where('is_active', true)->count(),
            'live_active' => $integrations
                ->where('is_active', true)
                ->where('environment', PaymentIntegration::ENV_LIVE)
                ->count(),
            'incomplete' => $incomplete,
            'default_label' => $default?->title,
            'default_missing' => $default === null,
            'failed_recent' => $failedRecent,
            'pending_recent' => $pendingRecent,
            'issues' => $incomplete + ($default === null ? 1 : 0) + $failedRecent + $pendingRecent,
        ];
    }

    private function siteHealth(array $can): array
    {
        if (!($can['siteSettingsView'] ?? false)) {
            return [
                'issues' => 0,
                'score' => 100,
                'checklist' => [],
            ];
        }

        $settings = SiteSetting::query()->first();
        $seoGeneratedAt = $settings?->seo_files_generated_at;
        $seoFresh = $seoGeneratedAt !== null && $seoGeneratedAt->gte(now()->subDays(7));
        $smtpReady = !$settings?->mail_notifications_enabled
            || (filled($settings?->smtp_host) && filled($settings?->smtp_port) && filled($settings?->mail_from_address));

        $checklist = [
            [
                'label' => 'SEO temel adresi',
                'ok' => filled($settings?->seo_base_url),
                'hint' => filled($settings?->seo_base_url) ? (string) $settings?->seo_base_url : 'Base URL girilmeli',
            ],
            [
                'label' => 'Sitemap içeriği',
                'ok' => filled($settings?->sitemap_xml_content),
                'hint' => filled($settings?->sitemap_xml_content) ? 'Hazır' : 'Otomatik içerik oluşturulmalı',
            ],
            [
                'label' => 'robots.txt',
                'ok' => filled($settings?->robots_txt_content),
                'hint' => filled($settings?->robots_txt_content) ? 'Hazır' : 'robots.txt içeriği eksik',
            ],
            [
                'label' => 'llms.txt',
                'ok' => filled($settings?->llms_txt_content),
                'hint' => filled($settings?->llms_txt_content) ? 'Hazır' : 'llms.txt içeriği eksik',
            ],
            [
                'label' => 'SEO dosya güncelliği',
                'ok' => $seoFresh,
                'hint' => $seoGeneratedAt ? $seoGeneratedAt->diffForHumans() : 'Henüz oluşturulmamış',
            ],
            [
                'label' => 'SMTP bildirimleri',
                'ok' => $smtpReady,
                'hint' => $settings?->mail_notifications_enabled ? 'Bildirim aktif' : 'Bildirim kapalı',
            ],
            [
                'label' => 'Bakım modu',
                'ok' => !((bool) $settings?->under_construction_enabled),
                'hint' => $settings?->under_construction_enabled ? 'Site bakım modunda' : 'Site yayına açık',
            ],
        ];

        $issues = collect($checklist)->where('ok', false)->count();

        return [
            'issues' => $issues,
            'score' => (int) round(((count($checklist) - $issues) / max(1, count($checklist))) * 100),
            'checklist' => $checklist,
        ];
    }

    private function contentQuality(array $can): array
    {
        $records = 0;
        $missingSeo = 0;
        $missingImages = 0;
        $drafts = 0;

        if ($can['blogView'] ?? false) {
            $records += BlogPost::query()->count();
            $missingSeo += BlogPost::query()->where(function ($query) {
                $query->whereNull('meta_title')->orWhere('meta_title', '')
                    ->orWhereNull('meta_description')->orWhere('meta_description', '');
            })->count();
            $missingImages += BlogPost::query()
                ->where(function ($query) {
                    $query->whereNull('featured_image_path')->orWhere('featured_image_path', '');
                })
                ->whereDoesntHave('featuredMedia')
                ->count();
            $drafts += BlogPost::query()->draft()->count();
        }

        if ($can['projectsView'] ?? false) {
            $records += Project::query()->count();
            $missingSeo += Project::query()->where(function ($query) {
                $query->whereNull('meta_title')->orWhere('meta_title', '')
                    ->orWhereNull('meta_description')->orWhere('meta_description', '');
            })->count();
            $missingImages += Project::query()
                ->where(function ($query) {
                    $query->whereNull('featured_image_path')->orWhere('featured_image_path', '');
                })
                ->whereDoesntHave('featuredMedia')
                ->count();
            $drafts += Project::query()->whereIn('status', [
                Project::STATUS_DRAFT,
                Project::STATUS_APPOINTMENT_PENDING,
                Project::STATUS_DEV_PENDING,
            ])->count();
        }

        if ($can['productsView'] ?? false) {
            $records += Product::query()->count();
            $missingSeo += Product::query()->where(function ($query) {
                $query->whereNull('meta_title')->orWhere('meta_title', '')
                    ->orWhereNull('meta_description')->orWhere('meta_description', '');
            })->count();
            $missingImages += Product::query()
                ->where(function ($query) {
                    $query->whereNull('featured_image_path')->orWhere('featured_image_path', '');
                })
                ->whereDoesntHave('featuredMedia')
                ->count();
            $drafts += Product::query()->whereIn('status', [
                Product::STATUS_DRAFT,
                Product::STATUS_APPOINTMENT_PENDING,
            ])->count();
        }

        if ($can['sitePagesView'] ?? false) {
            $records += SitePage::query()->count();
            $missingSeo += SitePage::query()->where(function ($query) {
                $query->whereNull('meta_title')->orWhere('meta_title', '')
                    ->orWhereNull('meta_description')->orWhere('meta_description', '');
            })->count();
            $missingImages += SitePage::query()
                ->whereNull('featured_media_id')
                ->where(function ($query) {
                    $query->whereNull('featured_image_path')->orWhere('featured_image_path', '');
                })
                ->count();
            $drafts += SitePage::query()->where('is_active', false)->count();
        }

        $checks = max(1, $records * 2);
        $missing = $missingSeo + $missingImages;

        return [
            'records' => $records,
            'missing_seo' => $missingSeo,
            'missing_images' => $missingImages,
            'drafts' => $drafts,
            'risk_total' => $missingSeo + $missingImages + $drafts,
            'score' => (int) max(0, round((($checks - $missing) / $checks) * 100)),
        ];
    }

    private function inventoryRisks(array $can): array
    {
        if (!($can['productsView'] ?? false)) {
            return [
                'low_stock' => 0,
                'out_of_stock' => 0,
                'missing_price' => 0,
                'missing_image' => 0,
                'stock_value' => 0.0,
                'risk_total' => 0,
            ];
        }

        $outOfStock = Product::query()
            ->whereNotNull('stock')
            ->where('stock', '<=', 0)
            ->count();
        $lowStock = Product::query()->lowStock()->where('stock', '>', 0)->count();
        $missingPrice = Product::query()
            ->where(function ($query) {
                $query->whereNull('price')->orWhere('price', '<=', 0);
            })
            ->count();
        $missingImage = Product::query()
            ->where(function ($query) {
                $query->whereNull('featured_image_path')->orWhere('featured_image_path', '');
            })
            ->whereDoesntHave('featuredMedia')
            ->count();

        return [
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
            'missing_price' => $missingPrice,
            'missing_image' => $missingImage,
            'stock_value' => (float) (Product::query()
                ->whereNotNull('stock')
                ->selectRaw('SUM(COALESCE(sale_price, price, 0) * stock) as total_value')
                ->value('total_value') ?? 0),
            'risk_total' => $outOfStock + $lowStock + $missingPrice + $missingImage,
        ];
    }

    private function memberStats(array $can, Carbon $now): array
    {
        if (!($can['membersView'] ?? false)) {
            return [
                'total' => 0,
                'active' => 0,
                'suspended' => 0,
                'documents' => 0,
                'new_today' => 0,
                'new_week' => 0,
                'new_month' => 0,
            ];
        }

        return [
            'total' => Member::query()->count(),
            'active' => Member::query()->active()->count(),
            'suspended' => Member::query()->suspended()->count(),
            'documents' => Member::query()->withDocument()->count(),
            'new_today' => Member::query()->where('created_at', '>=', $now->copy()->startOfDay())->count(),
            'new_week' => Member::query()->where('created_at', '>=', $now->copy()->subDays(6)->startOfDay())->count(),
            'new_month' => Member::query()->where('created_at', '>=', $now->copy()->subDays(29)->startOfDay())->count(),
        ];
    }

    private function appointmentPerformance(array $can, User $user, Carbon $now): array
    {
        if (!($can['appointmentsView'] ?? false)) {
            return [
                'completed_week' => 0,
                'cancelled_week' => 0,
                'no_show_week' => 0,
                'active_providers_week' => 0,
            ];
        }

        $query = Appointment::query();
        if (!$user->isSuperAdmin()) {
            $query->where('provider_id', $user->id);
        }

        $weekStart = $now->copy()->subDays(6)->startOfDay();

        return [
            'completed_week' => (clone $query)->where('status', Appointment::STATUS_COMPLETED)->where('start_at', '>=', $weekStart)->count(),
            'cancelled_week' => (clone $query)->whereIn('status', [
                Appointment::STATUS_CANCELLED_BY_MEMBER,
                Appointment::STATUS_CANCELLED_BY_PROVIDER,
            ])->where('start_at', '>=', $weekStart)->count(),
            'no_show_week' => (clone $query)->where('status', Appointment::STATUS_NO_SHOW)->where('start_at', '>=', $weekStart)->count(),
            'active_providers_week' => (clone $query)->where('start_at', '>=', $weekStart)->distinct('provider_id')->count('provider_id'),
        ];
    }

    private function supportSla(array $can, $messagesQuery, Carbon $now): array
    {
        if (!($can['messagesView'] ?? false)) {
            return [
                'overdue_unread' => 0,
                'urgent_unread' => 0,
                'oldest_unread_hours' => null,
                'oldest_unread_label' => 'Temiz',
            ];
        }

        $oldestUnread = (clone $messagesQuery)
            ->whereNull('read_at')
            ->oldest('created_at')
            ->first(['created_at']);
        $oldestHours = $oldestUnread?->created_at
            ? (int) $oldestUnread->created_at->diffInHours($now)
            : null;

        return [
            'overdue_unread' => (clone $messagesQuery)
                ->whereNull('read_at')
                ->where('created_at', '<=', $now->copy()->subDay())
                ->count(),
            'urgent_unread' => (clone $messagesQuery)
                ->whereNull('read_at')
                ->whereIn('priority', [ContactMessage::PRIORITY_HIGH, ContactMessage::PRIORITY_URGENT])
                ->count(),
            'oldest_unread_hours' => $oldestHours,
            'oldest_unread_label' => $oldestHours === null ? 'Temiz' : "{$oldestHours} saat",
        ];
    }

    private function translationHealth(array $can): array
    {
        if (!($can['siteLanguagesView'] ?? false)) {
            return [
                'active_languages' => 0,
                'translated_languages' => 0,
                'missing_total' => 0,
                'expected_total' => 0,
                'coverage' => 100,
                'default_missing' => false,
            ];
        }

        $languages = SiteLanguage::query()->active()->get(['code', 'is_default']);
        $translationLanguages = $languages->where('is_default', false)->pluck('code')->filter()->values();
        $expected = 0;
        $missing = 0;

        foreach ($translationLanguages as $locale) {
            if ($can['sitePagesView'] ?? false) {
                $count = SitePage::query()->count();
                $expected += $count;
                $missing += SitePage::query()
                    ->whereDoesntHave('translations', fn ($query) => $query->where('locale', $locale))
                    ->count();
            }

            if ($can['blogView'] ?? false) {
                $count = BlogPost::query()->count();
                $expected += $count;
                $missing += BlogPost::query()
                    ->whereDoesntHave('translations', fn ($query) => $query->where('locale', $locale))
                    ->count();
            }

            if ($can['projectsView'] ?? false) {
                $count = Project::query()->count();
                $expected += $count;
                $missing += Project::query()
                    ->whereDoesntHave('translations', fn ($query) => $query->where('locale', $locale))
                    ->count();
            }

            if ($can['productsView'] ?? false) {
                $count = Product::query()->count();
                $expected += $count;
                $missing += Product::query()
                    ->whereDoesntHave('translations', fn ($query) => $query->where('locale', $locale))
                    ->count();
            }
        }

        return [
            'active_languages' => $languages->count(),
            'translated_languages' => $translationLanguages->count(),
            'missing_total' => $missing,
            'expected_total' => $expected,
            'coverage' => $expected > 0 ? (int) round((($expected - $missing) / $expected) * 100) : 100,
            'default_missing' => $languages->where('is_default', true)->isEmpty(),
        ];
    }

    private function healthCards(
        array $can,
        array $orderStats,
        array $paymentHealth,
        array $siteHealth,
        array $contentQuality,
        array $inventoryRisks,
        array $memberStats,
        array $appointmentPerformance,
        array $supportSla,
        array $translationHealth
    ): array {
        return array_values(array_filter([
            ($can['ecommerceOrdersView'] ?? false) ? [
                'visibility_key' => 'health_ecommerce',
                'title' => 'E-Ticaret Nabzı',
                'value' => $this->money($orderStats['month_revenue']),
                'label' => '30 günlük ciro',
                'hint' => "{$orderStats['month_count']} sipariş, {$orderStats['requires_action']} aksiyon bekliyor",
                'badge' => "{$orderStats['today_count']} bugün",
                'badge_class' => 'kt-badge kt-badge-sm kt-badge-light-primary',
                'url' => route('admin.ecommerce.orders.index'),
                'action_label' => 'Siparişleri aç',
                'icon' => 'ki-filled ki-basket',
                'accent' => '#0ea5e9',
            ] : null,
            ($can['sitePaymentsView'] ?? false) ? [
                'visibility_key' => 'health_payments',
                'title' => 'Ödeme Sağlığı',
                'value' => (string) $paymentHealth['issues'],
                'label' => 'uyarı',
                'hint' => $paymentHealth['default_label'] ? "Varsayılan: {$paymentHealth['default_label']}" : 'Varsayılan sağlayıcı seçilmemiş',
                'badge' => "{$paymentHealth['active']} aktif",
                'badge_class' => $paymentHealth['issues'] > 0 ? 'kt-badge kt-badge-sm kt-badge-light-warning' : 'kt-badge kt-badge-sm kt-badge-light-success',
                'url' => route('admin.site.payments.index'),
                'action_label' => 'Ödemeleri aç',
                'icon' => 'ki-filled ki-credit-cart',
                'accent' => '#f97316',
            ] : null,
            ($can['siteSettingsView'] ?? false) ? [
                'visibility_key' => 'health_site',
                'title' => 'Site Hazırlığı',
                'value' => $siteHealth['score'] . '%',
                'label' => 'sağlık skoru',
                'hint' => "{$siteHealth['issues']} ayar dikkat istiyor",
                'badge' => $siteHealth['issues'] > 0 ? 'Kontrol gerekli' : 'Hazır',
                'badge_class' => $siteHealth['issues'] > 0 ? 'kt-badge kt-badge-sm kt-badge-light-warning' : 'kt-badge kt-badge-sm kt-badge-light-success',
                'url' => route('admin.site.settings.edit'),
                'action_label' => 'Ayarları aç',
                'icon' => 'ki-filled ki-setting-2',
                'accent' => '#64748b',
            ] : null,
            ($contentQuality['records'] ?? 0) > 0 ? [
                'visibility_key' => 'health_content',
                'title' => 'İçerik Kalitesi',
                'value' => $contentQuality['score'] . '%',
                'label' => 'tamamlanma',
                'hint' => "{$contentQuality['missing_seo']} SEO, {$contentQuality['missing_images']} görsel eksiği",
                'badge' => "{$contentQuality['drafts']} taslak",
                'badge_class' => $contentQuality['risk_total'] > 0 ? 'kt-badge kt-badge-sm kt-badge-light-warning' : 'kt-badge kt-badge-sm kt-badge-light-success',
                'url' => $this->firstContentRoute($can),
                'action_label' => 'İçerikleri aç',
                'icon' => 'ki-filled ki-notepad-edit',
                'accent' => '#3e97ff',
            ] : null,
            ($can['productsView'] ?? false) ? [
                'visibility_key' => 'health_inventory',
                'title' => 'Stok ve Katalog',
                'value' => (string) ($inventoryRisks['low_stock'] + $inventoryRisks['out_of_stock']),
                'label' => 'stok uyarısı',
                'hint' => "{$inventoryRisks['missing_price']} fiyat, {$inventoryRisks['missing_image']} görsel eksiği",
                'badge' => $this->money($inventoryRisks['stock_value']),
                'badge_class' => 'kt-badge kt-badge-sm kt-badge-light-primary',
                'url' => route('admin.products.index'),
                'action_label' => 'Ürünleri aç',
                'icon' => 'ki-filled ki-handcart',
                'accent' => '#f1416c',
            ] : null,
            ($can['messagesView'] ?? false) ? [
                'visibility_key' => 'health_support',
                'title' => 'Mesaj SLA',
                'value' => $supportSla['oldest_unread_label'],
                'label' => 'en eski okunmamış',
                'hint' => "{$supportSla['overdue_unread']} geciken, {$supportSla['urgent_unread']} öncelikli mesaj",
                'badge' => $supportSla['overdue_unread'] > 0 ? 'Gecikme var' : 'Temiz',
                'badge_class' => $supportSla['overdue_unread'] > 0 ? 'kt-badge kt-badge-sm kt-badge-light-danger' : 'kt-badge kt-badge-sm kt-badge-light-success',
                'url' => route('admin.messages.index'),
                'action_label' => 'Mesajları aç',
                'icon' => 'ki-filled ki-messages',
                'accent' => '#f6b100',
            ] : null,
            ($can['membersView'] ?? false) ? [
                'visibility_key' => 'health_members',
                'title' => 'Üyelikler',
                'value' => (string) $memberStats['active'],
                'label' => 'aktif üye',
                'hint' => "{$memberStats['new_month']} aylık yeni kayıt, {$memberStats['documents']} evraklı üye",
                'badge' => "{$memberStats['suspended']} askıda",
                'badge_class' => $memberStats['suspended'] > 0 ? 'kt-badge kt-badge-sm kt-badge-light-warning' : 'kt-badge kt-badge-sm kt-badge-light-success',
                'url' => route('admin.members.index'),
                'action_label' => 'Üyeleri aç',
                'icon' => 'ki-filled ki-users',
                'accent' => '#14b8a6',
            ] : null,
            ($can['appointmentsView'] ?? false) ? [
                'visibility_key' => 'health_appointments',
                'title' => 'Randevu Performansı',
                'value' => (string) $appointmentPerformance['completed_week'],
                'label' => 'tamamlanan',
                'hint' => "{$appointmentPerformance['cancelled_week']} iptal, {$appointmentPerformance['no_show_week']} gelmedi",
                'badge' => "{$appointmentPerformance['active_providers_week']} danışman",
                'badge_class' => 'kt-badge kt-badge-sm kt-badge-light-primary',
                'url' => route('admin.appointments.calendar'),
                'action_label' => 'Takvimi aç',
                'icon' => 'ki-filled ki-calendar-8',
                'accent' => '#7239ea',
            ] : null,
            ($can['siteLanguagesView'] ?? false) ? [
                'visibility_key' => 'health_translations',
                'title' => 'Çeviri Kapsamı',
                'value' => $translationHealth['coverage'] . '%',
                'label' => 'tamamlanma',
                'hint' => "{$translationHealth['missing_total']} eksik çeviri, {$translationHealth['translated_languages']} hedef dil",
                'badge' => "{$translationHealth['active_languages']} aktif dil",
                'badge_class' => ($translationHealth['missing_total'] > 0 || $translationHealth['default_missing']) ? 'kt-badge kt-badge-sm kt-badge-light-warning' : 'kt-badge kt-badge-sm kt-badge-light-success',
                'url' => route('admin.site.languages.index'),
                'action_label' => 'Dilleri aç',
                'icon' => 'ki-filled ki-abstract-26',
                'accent' => '#8b5cf6',
            ] : null,
        ]));
    }

    private function riskGroups(array $can, array $paymentHealth, array $siteHealth, array $contentQuality, array $inventoryRisks, array $supportSla, array $translationHealth): array
    {
        return array_values(array_filter([
            ($can['sitePaymentsView'] ?? false) && $paymentHealth['issues'] > 0 ? [
                'title' => 'Ödeme Riskleri',
                'icon' => 'ki-filled ki-credit-cart',
                'accent' => '#f97316',
                'url' => route('admin.site.payments.index'),
                'items' => [
                    ['label' => 'Eksik yapılandırma', 'value' => $paymentHealth['incomplete']],
                    ['label' => 'Son 7 gün başarısız hareket', 'value' => $paymentHealth['failed_recent']],
                    ['label' => 'Bekleyen ödeme hareketi', 'value' => $paymentHealth['pending_recent']],
                ],
            ] : null,
            ($can['siteSettingsView'] ?? false) && $siteHealth['issues'] > 0 ? [
                'title' => 'Site Yayın Hazırlığı',
                'icon' => 'ki-filled ki-setting-2',
                'accent' => '#64748b',
                'url' => route('admin.site.settings.edit'),
                'items' => collect($siteHealth['checklist'])
                    ->where('ok', false)
                    ->map(fn (array $item) => ['label' => $item['label'], 'value' => $item['hint']])
                    ->values()
                    ->all(),
            ] : null,
            ($contentQuality['risk_total'] ?? 0) > 0 ? [
                'title' => 'İçerik Kalitesi',
                'icon' => 'ki-filled ki-notepad-edit',
                'accent' => '#3e97ff',
                'url' => $this->firstContentRoute($can),
                'items' => [
                    ['label' => 'SEO eksiği', 'value' => $contentQuality['missing_seo']],
                    ['label' => 'Görsel eksiği', 'value' => $contentQuality['missing_images']],
                    ['label' => 'Taslak / pasif içerik', 'value' => $contentQuality['drafts']],
                ],
            ] : null,
            ($can['productsView'] ?? false) && $inventoryRisks['risk_total'] > 0 ? [
                'title' => 'Katalog ve Stok',
                'icon' => 'ki-filled ki-handcart',
                'accent' => '#f1416c',
                'url' => route('admin.products.index'),
                'items' => [
                    ['label' => 'Stokta olmayan ürün', 'value' => $inventoryRisks['out_of_stock']],
                    ['label' => 'Düşük stoklu ürün', 'value' => $inventoryRisks['low_stock']],
                    ['label' => 'Fiyatı eksik ürün', 'value' => $inventoryRisks['missing_price']],
                ],
            ] : null,
            ($can['messagesView'] ?? false) && $supportSla['overdue_unread'] > 0 ? [
                'title' => 'Mesaj SLA',
                'icon' => 'ki-filled ki-messages',
                'accent' => '#f6b100',
                'url' => route('admin.messages.index'),
                'items' => [
                    ['label' => '24 saati aşan okunmamış', 'value' => $supportSla['overdue_unread']],
                    ['label' => 'Öncelikli okunmamış', 'value' => $supportSla['urgent_unread']],
                    ['label' => 'En eski okunmamış', 'value' => $supportSla['oldest_unread_label']],
                ],
            ] : null,
            ($can['siteLanguagesView'] ?? false) && ($translationHealth['missing_total'] > 0 || $translationHealth['default_missing']) ? [
                'title' => 'Çeviri Kapsamı',
                'icon' => 'ki-filled ki-abstract-26',
                'accent' => '#8b5cf6',
                'url' => route('admin.site.languages.index'),
                'items' => [
                    ['label' => 'Eksik çeviri', 'value' => $translationHealth['missing_total']],
                    ['label' => 'Hedef dil', 'value' => $translationHealth['translated_languages']],
                    ['label' => 'Varsayılan dil', 'value' => $translationHealth['default_missing'] ? 'Eksik' : 'Hazır'],
                ],
            ] : null,
        ]));
    }

    private function recentOrders(array $can): Collection
    {
        if (!($can['ecommerceOrdersView'] ?? false)) {
            return collect();
        }

        return EcommerceOrder::query()
            ->withCount('items')
            ->latest('ordered_at')
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (EcommerceOrder $order) => [
                'id' => 'order-' . $order->id,
                'start' => $order->ordered_at?->toIso8601String(),
                'title' => $order->order_number,
                'subtitle' => trim(($order->customer_name ?: 'Müşteri') . ' | ' . $order->money()),
                'description' => "{$order->items_count} kalem | " . $order->paymentStatusLabel() . ' | ' . $order->fulfillmentStatusLabel(),
                'status' => $order->statusLabel(),
                'badgeClass' => $order->statusBadgeClass(),
                'variant' => match ($order->status) {
                    EcommerceOrder::STATUS_CANCELLED, EcommerceOrder::STATUS_REFUNDED => 'danger',
                    EcommerceOrder::STATUS_COMPLETED => 'success',
                    EcommerceOrder::STATUS_PROCESSING, EcommerceOrder::STATUS_SHIPPED => 'primary',
                    default => 'warning',
                },
                'icon' => 'ki-filled ki-basket',
                'url' => route('admin.ecommerce.orders.show', $order),
            ]);
    }

    private function commercePipeline(array $orderStats): array
    {
        return [
            [
                'label' => 'Onay bekleyen',
                'value' => $orderStats['pending'],
                'badge' => 'kt-badge kt-badge-sm kt-badge-light-warning',
            ],
            [
                'label' => 'Ödeme bekleyen',
                'value' => $orderStats['awaiting_payment'],
                'badge' => 'kt-badge kt-badge-sm kt-badge-light-primary',
            ],
            [
                'label' => 'Hazırlanacak',
                'value' => $orderStats['preparing'],
                'badge' => 'kt-badge kt-badge-sm kt-badge-light-info',
            ],
            [
                'label' => 'İade / sorun',
                'value' => $orderStats['refunds'] + $orderStats['failed_payment'],
                'badge' => 'kt-badge kt-badge-sm kt-badge-light-danger',
            ],
        ];
    }

    private function firstContentRoute(array $can): ?string
    {
        if ($can['blogView'] ?? false) {
            return route('admin.blog.index');
        }

        if ($can['projectsView'] ?? false) {
            return route('admin.projects.index');
        }

        if ($can['productsView'] ?? false) {
            return route('admin.products.index');
        }

        if ($can['sitePagesView'] ?? false) {
            return route('admin.site.pages.index');
        }

        return null;
    }

    private function money(float $amount, string $currency = 'TRY'): string
    {
        return number_format($amount, 2, ',', '.') . ' ' . $currency;
    }

    private function dashboardSectionDefinitions(array $capabilities): array
    {
        return DashboardSectionRegistry::definitions([
            'messagesView' => (bool) ($capabilities['messagesView'] ?? false),
            'appointmentsView' => (bool) ($capabilities['appointmentsView'] ?? false),
            'auditView' => (bool) ($capabilities['auditView'] ?? false),
            'blogView' => (bool) ($capabilities['blogView'] ?? false),
            'projectsView' => (bool) ($capabilities['projectsView'] ?? false),
            'productsView' => (bool) ($capabilities['productsView'] ?? false),
            'mediaView' => (bool) ($capabilities['mediaView'] ?? false),
            'galleriesView' => (bool) ($capabilities['galleriesView'] ?? false),
            'categoriesView' => (bool) ($capabilities['categoriesView'] ?? false),
            'usersView' => (bool) ($capabilities['usersView'] ?? false),
            'trashView' => (bool) ($capabilities['trashView'] ?? false),
            'ecommerceOrdersView' => (bool) ($capabilities['ecommerceOrdersView'] ?? false),
            'membersView' => (bool) ($capabilities['membersView'] ?? false),
            'sitePaymentsView' => (bool) ($capabilities['sitePaymentsView'] ?? false),
            'siteSettingsView' => (bool) ($capabilities['siteSettingsView'] ?? false),
            'siteLanguagesView' => (bool) ($capabilities['siteLanguagesView'] ?? false),
            'sitePagesView' => (bool) ($capabilities['sitePagesView'] ?? false),
        ]);
    }

    private function resolveDashboardSectionVisibility(User $user, array $dashboardSections): array
    {
        $stored = $user->dashboardPreference?->visible_sections;
        $defaults = DashboardSectionRegistry::defaults($dashboardSections);

        if (!is_array($stored) || $stored === []) {
            return $defaults;
        }

        $visibility = [];
        foreach ($defaults as $key => $default) {
            $visibility[$key] = array_key_exists($key, $stored)
                ? (bool) $stored[$key]
                : (bool) $default;
        }

        return $visibility;
    }

    private function resolveDashboardSectionOrder(User $user, array $dashboardSections): array
    {
        $availableKeys = DashboardSectionRegistry::availableKeys($dashboardSections);
        $stored = $user->dashboardPreference?->section_order;

        if (!is_array($stored) || $stored === []) {
            return $availableKeys;
        }

        return collect($stored)
            ->map(fn ($key) => (string) $key)
            ->filter(fn ($key) => in_array($key, $availableKeys, true))
            ->unique()
            ->merge($availableKeys)
            ->unique()
            ->values()
            ->all();
    }

    private function dashboardSectionOrderIndex(array $sectionOrder): array
    {
        return collect($sectionOrder)
            ->values()
            ->mapWithKeys(fn (string $key, int $index) => [$key => ($index + 1) * 10])
            ->all();
    }

    private function sortDashboardItemsByOrder(array $items, array $sectionOrder): array
    {
        $orderIndex = array_flip($sectionOrder);

        return collect($items)
            ->values()
            ->sortBy(fn (array $item, int $index) => $orderIndex[$item['visibility_key'] ?? ''] ?? (10000 + $index))
            ->values()
            ->all();
    }

    private function dashboardSectionsForManagement(array $dashboardSections, array $visibility, array $orderIndex): Collection
    {
        return collect($dashboardSections)
            ->filter(fn (array $section) => ($section['available'] ?? false) === true)
            ->map(function (array $section, string $key) use ($visibility, $orderIndex) {
                $section['key'] = $key;
                $section['visible'] = (bool) ($visibility[$key] ?? false);
                $section['children'] = collect($section['children'] ?? [])
                    ->filter(fn (array $child) => ($child['available'] ?? false) === true)
                    ->map(function (array $child, string $childKey) use ($visibility) {
                        $child['key'] = $childKey;
                        $child['visible'] = (bool) ($visibility[$childKey] ?? false);

                        return $child;
                    })
                    ->sortBy(fn (array $child) => $orderIndex[$child['key']] ?? PHP_INT_MAX)
                    ->values()
                    ->all();

                return $section;
            })
            ->sortBy(fn (array $section) => $orderIndex[$section['key']] ?? PHP_INT_MAX)
            ->values();
    }

    private function monthBuckets(Carbon $now, int $months): array
    {
        $labels = [];
        $keys = [];
        $start = $now->copy()->startOfMonth()->subMonths($months - 1);

        for ($i = 0; $i < $months; $i += 1) {
            $point = $start->copy()->addMonths($i);
            $labels[] = $point->format('M Y');
            $keys[] = $point->format('Y-m');
        }

        return [$labels, $keys, $start];
    }

    private function dayBuckets(Carbon $now, int $days): array
    {
        $labels = [];
        $keys = [];
        $start = $now->copy()->startOfDay();

        for ($i = 0; $i < $days; $i += 1) {
            $point = $start->copy()->addDays($i);
            $labels[] = $point->format('d M');
            $keys[] = $point->format('Y-m-d');
        }

        return [$labels, $keys, $start];
    }

    private function seriesByMonth($query, Carbon $from, array $keys, string $column = 'created_at'): array
    {
        $items = $query
            ->where($column, '>=', $from)
            ->get([$column])
            ->groupBy(function ($item) use ($column) {
                return optional($item->{$column})->timezone('Europe/Istanbul')->format('Y-m');
            });

        return collect($keys)
            ->map(fn (string $key) => (int) ($items->get($key)?->count() ?? 0))
            ->all();
    }

    private function seriesByDay($query, Carbon $from, array $keys, string $column = 'created_at'): array
    {
        $items = $query
            ->where($column, '>=', $from)
            ->where($column, '<=', $from->copy()->addDays(count($keys) - 1)->endOfDay())
            ->get([$column])
            ->groupBy(function ($item) use ($column) {
                return optional($item->{$column})->timezone('Europe/Istanbul')->format('Y-m-d');
            });

        return collect($keys)
            ->map(fn (string $key) => (int) ($items->get($key)?->count() ?? 0))
            ->all();
    }

    private function recentContent(array $can): Collection
    {
        $items = collect();

        if ($can['blogView']) {
            $items = $items->concat(
                BlogPost::query()
                    ->latest('updated_at')
                    ->limit(3)
                    ->get(['id', 'title', 'is_published', 'updated_at'])
                    ->map(fn (BlogPost $post) => [
                        'type' => 'Blog',
                        'title' => $post->title,
                        'meta' => $post->is_published ? 'Yayında' : 'Taslak',
                        'badge' => $post->is_published ? 'kt-badge kt-badge-sm kt-badge-light-success' : 'kt-badge kt-badge-sm kt-badge-light',
                        'url' => route('admin.blog.edit', $post),
                        'updated_at' => $post->updated_at,
                    ])
            );
        }

        if ($can['projectsView']) {
            $items = $items->concat(
                Project::query()
                    ->latest('updated_at')
                    ->limit(3)
                    ->get(['id', 'title', 'status', 'updated_at'])
                    ->map(fn (Project $project) => [
                        'type' => 'Proje',
                        'title' => $project->title,
                        'meta' => Project::statusLabel($project->status),
                        'badge' => Project::statusBadgeClass($project->status),
                        'url' => route('admin.projects.edit', $project),
                        'updated_at' => $project->updated_at,
                    ])
            );
        }

        if ($can['productsView']) {
            $items = $items->concat(
                Product::query()
                    ->latest('updated_at')
                    ->limit(3)
                    ->get(['id', 'title', 'status', 'updated_at'])
                    ->map(fn (Product $product) => [
                        'type' => 'Ürün',
                        'title' => $product->title,
                        'meta' => Product::statusLabel($product->status),
                        'badge' => Product::statusBadgeClass($product->status),
                        'url' => route('admin.products.edit', $product),
                        'updated_at' => $product->updated_at,
                    ])
            );
        }

        return $items;
    }
}
