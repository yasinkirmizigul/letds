<?php

namespace App\Http\Controllers\Admin\Dash;

use App\Http\Controllers\Controller;
use App\Models\Admin\AuditLog\AuditLog;
use App\Models\Admin\BlogPost\BlogPost;
use App\Models\Admin\Category;
use App\Models\Admin\Gallery\Gallery;
use App\Models\Admin\Media\Media;
use App\Models\Admin\Product\Product;
use App\Models\Admin\Project\Project;
use App\Models\Admin\User\User;
use App\Models\Appointment\Appointment;
use App\Models\ContactMessage;
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

        $contentTotal = $blogStats['total'] + $projectStats['total'] + $productStats['total'];
        $focusTotal = $messageStats['unread'] + $messageStats['urgent'] + $productStats['lowStock'] + $trashTotal + $appointmentsToday;

        $kpis = [
            [
                'label' => 'İçerik havuzu',
                'value' => $contentTotal,
                'hint' => "{$blogStats['total']} blog, {$projectStats['total']} proje, {$productStats['total']} ürün",
                'icon' => 'ki-filled ki-element-11',
                'accent' => '#3e97ff',
            ],
            [
                'label' => 'Okunmamış mesaj',
                'value' => $messageStats['unread'],
                'hint' => $messageStats['urgent'] > 0 ? "{$messageStats['urgent']} yüksek öncelik" : 'Mesaj kutusunu takip et',
                'icon' => 'ki-filled ki-messages',
                'accent' => '#f6b100',
            ],
            [
                'label' => 'Bu hafta randevu',
                'value' => $appointmentsWeek,
                'hint' => $appointmentsToday > 0 ? "Bugün {$appointmentsToday} randevu var" : 'Takvim sakin gözüküyor',
                'icon' => 'ki-filled ki-calendar-8',
                'accent' => '#17c653',
            ],
            [
                'label' => 'Düşük stok',
                'value' => $productStats['lowStock'],
                'hint' => $productStats['active'] > 0 ? "{$productStats['active']} aktif ürün yayınlı" : 'Stok akışına göz at',
                'icon' => 'ki-filled ki-handcart',
                'accent' => '#f1416c',
            ],
            [
                'label' => $can['auditView'] ? 'Sistem uyarısı' : 'Çöp kutusu',
                'value' => $can['auditView'] ? $auditStats['errors'] : $trashTotal,
                'hint' => $can['auditView']
                    ? ($auditStats['today'] > 0 ? "Bugün {$auditStats['today']} log oluştu" : 'Kritik logları takip et')
                    : "{$trashTotal} kayıt geri yüklenebilir",
                'icon' => $can['auditView'] ? 'ki-filled ki-fingerprint-scanning' : 'ki-filled ki-trash',
                'accent' => $can['auditView'] ? '#7239ea' : '#1f2937',
            ],
        ];

        $quickActions = array_values(array_filter([
            $can['messagesView'] ? [
                'label' => 'Mesaj kutusu',
                'url' => route('admin.messages.index'),
                'style' => 'kt-btn-primary',
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
                    return [
                        'subject' => $message->subject,
                        'sender' => $message->sender_full_name ?: 'Bilinmeyen',
                        'time' => $message->created_at?->diffForHumans(),
                        'priority_label' => ContactMessage::priorityLabel($message->priority),
                        'priority_badge' => ContactMessage::priorityBadgeClass($message->priority),
                        'status_badge' => $message->isRead() ? 'kt-badge kt-badge-sm kt-badge-light-success' : 'kt-badge kt-badge-sm kt-badge-light-warning',
                        'status_label' => $message->isRead() ? 'Okundu' : 'Okunmadı',
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
                        'title' => $memberName !== '' ? $memberName : 'Randevu',
                        'provider' => $user->isSuperAdmin() ? ($appointment->provider?->name ?: '-') : null,
                        'time' => $appointment->start_at?->timezone('Europe/Istanbul')->format('d M H:i'),
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
                return $item;
            });

        $dashboardSections = $this->dashboardSectionDefinitions($can);
        $dashboardSectionVisibility = $this->resolveDashboardSectionVisibility($user, $dashboardSections);
        $hasVisibleDashboardSection = collect($dashboardSectionVisibility)->contains(fn (bool $visible) => $visible === true);

        $recentAuditIssues = $can['auditView']
            ? AuditLog::query()
                ->where('status', '>=', 400)
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn (AuditLog $log) => [
                    'status' => (int) $log->status,
                    'route' => $log->route ?: ($log->uri ?: 'request'),
                    'method' => strtoupper((string) $log->method),
                    'time' => $log->created_at?->diffForHumans(),
                    'url' => route('admin.audit-logs.show', $log),
                ])
                ->values()
            : collect();

        return view('admin.pages.dash.index', [
            'pageTitle' => 'Dashboard',
            'pageDescription' => 'Admin Dashboard',
            'greeting' => $this->greetingForHour((int) $now->format('H')),
            'heroSummary' => $this->heroSummary($user, $appointmentsToday, $messageStats['unread'], $productStats['lowStock'], $trashTotal),
            'quickActions' => $quickActions,
            'focusItems' => $focusItems,
            'kpis' => $kpis,
            'moduleCards' => $moduleCards,
            'monthlyActivity' => $monthlyActivity,
            'actionChart' => $actionChart,
            'scheduleChart' => $scheduleChart,
            'recentMessages' => $recentMessages,
            'upcomingAppointments' => $upcomingAppointments,
            'recentContent' => $recentContent,
            'recentAuditIssues' => $recentAuditIssues,
            'canAudit' => $can['auditView'],
            'canAppointments' => $can['appointmentsView'],
            'focusTotal' => $focusTotal,
            'nowLabel' => $now->format('d M Y, H:i'),
            'dashboardSections' => $dashboardSections,
            'dashboardSectionVisibility' => $dashboardSectionVisibility,
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
        ];

        $dashboardSections = $this->dashboardSectionDefinitions($capabilities);
        $dashboardSectionVisibility = $this->resolveDashboardSectionVisibility($user, $dashboardSections);

        $groupedSections = collect($dashboardSections)
            ->filter(fn (array $section) => ($section['available'] ?? false) === true)
            ->map(function (array $section, string $key) use ($dashboardSectionVisibility) {
                $section['key'] = $key;
                $section['visible'] = (bool) ($dashboardSectionVisibility[$key] ?? false);

                return $section;
            })
            ->groupBy('group');

        return view('admin.pages.dash.manage', [
            'pageTitle' => 'Dashboard Yönetimi',
            'pageDescription' => 'Dashboard Yönetimi',
            'dashboardSectionGroups' => $groupedSections,
            'dashboardSectionVisibility' => $dashboardSectionVisibility,
            'activeSectionCount' => collect($dashboardSectionVisibility)->filter()->count(),
            'availableSectionCount' => collect($dashboardSections)->filter(fn (array $section) => ($section['available'] ?? false) === true)->count(),
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
        ];

        $dashboardSections = $this->dashboardSectionDefinitions($capabilities);
        $defaults = DashboardSectionRegistry::defaults($dashboardSections);

        $validated = $request->validate([
            'action' => ['nullable', 'string', 'in:save,reset'],
            'visible_sections' => ['nullable', 'array'],
            'visible_sections.*' => ['string'],
        ]);

        if (($validated['action'] ?? 'save') === 'reset') {
            $user->dashboardPreference()->delete();

            return redirect()
                ->route('admin.dashboard.manage')
                ->with('success', 'Dashboard görünümü varsayılan ayarlara döndürüldü.');
        }

        $availableKeys = collect($dashboardSections)
            ->filter(fn (array $section) => ($section['available'] ?? false) === true)
            ->keys()
            ->all();

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

        if ($visibleSections === $defaults) {
            $user->dashboardPreference()->delete();
        } else {
            $user->dashboardPreference()->updateOrCreate(
                ['user_id' => $user->id],
                ['visible_sections' => $visibleSections]
            );
        }

        return redirect()
            ->route('admin.dashboard.manage')
            ->with('success', 'Dashboard görünürlüğü güncellendi.');
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

    private function heroSummary(User $user, int $appointmentsToday, int $unreadMessages, int $lowStock, int $trashTotal): string
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

        if ($trashTotal > 0) {
            $parts[] = "{$trashTotal} silinmiş kayıt";
        }

        if (count($parts) === 0) {
            return "{$user->name}, panel sakin görünüyor. Hemen yönetmek istediğin modülü açıp günlük akışı hızlandırabilirsin.";
        }

        return "{$user->name}, " . implode(', ', $parts) . ' seni bekliyor.';
    }

    private function dashboardSectionDefinitions(array $capabilities): array
    {
        return DashboardSectionRegistry::definitions([
            'messagesView' => (bool) ($capabilities['messagesView'] ?? false),
            'appointmentsView' => (bool) ($capabilities['appointmentsView'] ?? false),
            'auditView' => (bool) ($capabilities['auditView'] ?? false),
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
        foreach ($dashboardSections as $key => $section) {
            if (($section['available'] ?? false) !== true) {
                continue;
            }

            $visibility[$key] = array_key_exists($key, $stored)
                ? (bool) $stored[$key]
                : (bool) ($section['default'] ?? true);
        }

        return $visibility;
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
