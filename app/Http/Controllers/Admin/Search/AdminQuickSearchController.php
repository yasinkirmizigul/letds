<?php

namespace App\Http\Controllers\Admin\Search;

use App\Http\Controllers\Controller;
use App\Models\Admin\BlogPost\BlogPost;
use App\Models\Admin\Category;
use App\Models\Admin\Ecommerce\EcommerceOrder;
use App\Models\Admin\Gallery\Gallery;
use App\Models\Admin\Media\Media;
use App\Models\Admin\Product\Product;
use App\Models\Admin\Project\Project;
use App\Models\Admin\User\User;
use App\Models\Appointment\Appointment;
use App\Models\ContactMessage;
use App\Models\Member;
use App\Models\Site\PaymentIntegration;
use App\Models\Site\SitePage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AdminQuickSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user?->canAccessBackoffice(), 403);

        $query = trim((string) $request->string('q'));
        $limit = max(3, min(8, (int) $request->integer('limit', 5)));

        $groups = $query === ''
            ? $this->suggestionGroups($user)
            : $this->searchGroups($user, $query, $limit);

        return response()->json([
            'ok' => true,
            'query' => $query,
            'min_length' => 2,
            'total' => collect($groups)->sum(fn (array $group) => count($group['items'] ?? [])),
            'groups' => array_values($groups),
        ]);
    }

    private function suggestionGroups(User $user): array
    {
        $items = [
            $this->shortcut($user, 'Kontrol Paneli', 'Yönetim merkezi ve özet metrikler', 'admin.dashboard', 'ki-filled ki-element-11', null, ['admin']),
            $this->shortcut($user, 'Sipariş Yönetimi', 'E-ticaret siparişleri, ödeme ve kargo akışı', 'admin.ecommerce.orders.index', 'ki-filled ki-basket', 'ecommerce_orders.view'),
            $this->shortcut($user, 'Yeni Sipariş', 'Panelden manuel sipariş oluştur', 'admin.ecommerce.orders.create', 'ki-filled ki-plus', 'ecommerce_orders.create'),
            $this->shortcut($user, 'Ürün Yönetimi', 'Ürün kodu, fiyat, stok ve galeri yönetimi', 'admin.products.index', 'ki-filled ki-handcart', 'products.view'),
            $this->shortcut($user, 'Mesajlar', 'Panel kullanıcılarına gelen iletişim mesajları', 'admin.messages.index', 'ki-filled ki-messages', null, ['admin']),
            $this->shortcut($user, 'Randevu Takvimi', 'Takvim ve randevu operasyonu', 'admin.appointments.calendar', 'ki-filled ki-calendar-8', 'appointments.view'),
            $this->shortcut($user, 'Site Ayarları', 'SMTP, SEO dosyaları ve genel site ayarları', 'admin.site.settings.edit', 'ki-filled ki-setting-2', 'site_settings.view'),
            $this->shortcut($user, 'Ödeme Entegrasyonları', 'Sanal POS ve ödeme sağlayıcıları', 'admin.site.payments.index', 'ki-filled ki-two-credit-cart', 'site_payments.view'),
        ];

        return [[
            'key' => 'suggestions',
            'label' => 'Hızlı Erişim',
            'items' => array_values(array_filter($items)),
        ]];
    }

    private function searchGroups(User $user, string $query, int $limit): array
    {
        if (mb_strlen($query) < 2) {
            return $this->suggestionGroups($user);
        }

        $groups = [
            $this->navigationGroup($user, $query, $limit),
            $this->ordersGroup($user, $query, $limit),
            $this->productsGroup($user, $query, $limit),
            $this->contentGroup($user, $query, $limit),
            $this->peopleGroup($user, $query, $limit),
            $this->commerceSettingsGroup($user, $query, $limit),
            $this->libraryGroup($user, $query, $limit),
            $this->appointmentsGroup($user, $query, $limit),
            $this->messagesGroup($user, $query, $limit),
        ];

        return array_values(array_filter($groups, fn (?array $group) => $group && count($group['items'] ?? []) > 0));
    }

    private function navigationGroup(User $user, string $query, int $limit): ?array
    {
        $items = collect(config('admin_menu', []))
            ->flatMap(fn (array $item) => $this->flattenMenuItem($item))
            ->filter(fn (array $item) => $this->menuItemAllowed($user, $item))
            ->filter(function (array $item) use ($query) {
                $haystack = Str::lower(($item['title'] ?? '') . ' ' . ($item['route'] ?? ''));
                return str_contains($haystack, Str::lower($query));
            })
            ->take($limit)
            ->map(fn (array $item) => [
                'title' => (string) ($item['title'] ?? 'Panel Sayfası'),
                'subtitle' => 'Panel sayfasına git',
                'url' => $this->routeUrl((string) ($item['route'] ?? 'admin.dashboard')),
                'icon' => (string) ($item['icon'] ?? 'ki-filled ki-screen'),
                'badge' => 'Sayfa',
                'badge_class' => 'kt-badge kt-badge-sm kt-badge-light-primary',
            ])
            ->values()
            ->all();

        return $this->group('navigation', 'Panel Sayfaları', $items);
    }

    private function ordersGroup(User $user, string $query, int $limit): ?array
    {
        if (!$this->can($user, 'ecommerce_orders.view') || !$this->hasTable('ecommerce_orders')) {
            return null;
        }

        $items = EcommerceOrder::query()
            ->with('items:id,order_id,product_title')
            ->search($query)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (EcommerceOrder $order) => [
                'title' => $order->order_number . ' · ' . $order->customer_name,
                'subtitle' => trim(($order->customer_email ?: 'E-posta yok') . ' · ' . $order->money()),
                'url' => route('admin.ecommerce.orders.show', $order),
                'icon' => 'ki-filled ki-basket',
                'badge' => $order->statusLabel(),
                'badge_class' => $order->statusBadgeClass(),
            ])
            ->all();

        return $this->group('orders', 'Siparişler', $items);
    }

    private function productsGroup(User $user, string $query, int $limit): ?array
    {
        if (!$this->can($user, 'products.view')) {
            return null;
        }

        $items = Product::query()
            ->search($query)
            ->latest('updated_at')
            ->limit($limit)
            ->get()
            ->map(fn (Product $product) => [
                'title' => (string) $product->title,
                'subtitle' => trim(($product->sku ? 'Ürün kodu: ' . $product->sku : 'Ürün kodu yok') . ' · ' . ($product->brand ?: 'Marka yok')),
                'url' => $this->can($user, 'products.update') ? route('admin.products.edit', $product) : route('admin.products.index', ['q' => $query]),
                'icon' => 'ki-filled ki-handcart',
                'badge' => Product::statusLabel($product->status),
                'badge_class' => Product::statusBadgeClass($product->status),
            ])
            ->all();

        return $this->group('products', 'Ürünler', $items);
    }

    private function contentGroup(User $user, string $query, int $limit): ?array
    {
        $items = [];

        if ($this->can($user, 'blog.view')) {
            $items = array_merge($items, BlogPost::query()
                ->search($query)
                ->latest('updated_at')
                ->limit($limit)
                ->get()
                ->map(fn (BlogPost $post) => [
                    'title' => (string) $post->title,
                    'subtitle' => $post->excerptPreview(82) ?: '/' . $post->slug,
                    'url' => $this->can($user, 'blog.update') ? route('admin.blog.edit', $post) : route('admin.blog.index', ['q' => $query]),
                    'icon' => 'ki-filled ki-book',
                    'badge' => $post->is_published ? 'Yayında' : 'Taslak',
                    'badge_class' => $post->is_published ? 'kt-badge kt-badge-sm kt-badge-light-success' : 'kt-badge kt-badge-sm kt-badge-light',
                ])
                ->all());
        }

        if ($this->can($user, 'projects.view')) {
            $items = array_merge($items, Project::query()
                ->search($query)
                ->latest('updated_at')
                ->limit($limit)
                ->get()
                ->map(fn (Project $project) => [
                    'title' => (string) $project->title,
                    'subtitle' => $project->excerptPreview(82) ?: '/' . $project->slug,
                    'url' => $this->can($user, 'projects.update') ? route('admin.projects.edit', $project) : route('admin.projects.index', ['q' => $query]),
                    'icon' => 'ki-filled ki-briefcase',
                    'badge' => Project::statusLabel($project->status),
                    'badge_class' => Project::statusBadgeClass($project->status),
                ])
                ->all());
        }

        if ($this->can($user, 'site_pages.view')) {
            $items = array_merge($items, SitePage::query()
                ->search($query)
                ->latest('updated_at')
                ->limit($limit)
                ->get()
                ->map(fn (SitePage $page) => [
                    'title' => (string) $page->title,
                    'subtitle' => $page->excerptPreview(82) ?: '/' . $page->slug,
                    'url' => $this->can($user, 'site_pages.update') ? route('admin.site.pages.edit', $page) : route('admin.site.pages.index', ['q' => $query]),
                    'icon' => $page->icon_class ?: 'ki-filled ki-document',
                    'badge' => $page->is_active ? 'Aktif' : 'Pasif',
                    'badge_class' => $page->is_active ? 'kt-badge kt-badge-sm kt-badge-light-success' : 'kt-badge kt-badge-sm kt-badge-light',
                ])
                ->all());
        }

        return $this->group('content', 'İçerik', collect($items)->take($limit * 2)->values()->all());
    }

    private function peopleGroup(User $user, string $query, int $limit): ?array
    {
        $items = [];

        if ($this->can($user, 'members.view')) {
            $items = array_merge($items, Member::query()
                ->search($query)
                ->latest()
                ->limit($limit)
                ->get()
                ->map(fn (Member $member) => [
                    'title' => $member->full_name ?: $member->email,
                    'subtitle' => trim(($member->email ?: 'E-posta yok') . ' · ' . ($member->phone ?: 'Telefon yok')),
                    'url' => route('admin.members.show', $member),
                    'icon' => 'ki-filled ki-profile-circle',
                    'badge' => $member->statusLabel(),
                    'badge_class' => $member->statusBadgeClass(),
                ])
                ->all());
        }

        if ($this->can($user, 'users.view')) {
            $like = $this->like($query);
            $items = array_merge($items, User::query()
                ->where(function (Builder $builder) use ($like) {
                    $builder
                        ->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('title', 'like', $like)
                        ->orWhere('company', 'like', $like);
                })
                ->latest()
                ->limit($limit)
                ->get()
                ->map(fn (User $adminUser) => [
                    'title' => (string) $adminUser->name,
                    'subtitle' => trim(($adminUser->email ?: 'E-posta yok') . ' · ' . ($adminUser->title ?: $adminUser->badgeLabel())),
                    'url' => route('admin.users.profile', $adminUser),
                    'icon' => 'ki-filled ki-user',
                    'badge' => $adminUser->badgeLabel(),
                    'badge_class' => 'kt-badge kt-badge-sm kt-badge-light-primary',
                ])
                ->all());
        }

        return $this->group('people', 'Kişiler', collect($items)->take($limit * 2)->values()->all());
    }

    private function commerceSettingsGroup(User $user, string $query, int $limit): ?array
    {
        $items = [];

        if ($this->can($user, 'site_payments.view')) {
            $items = array_merge($items, PaymentIntegration::query()
                ->search($query)
                ->orderByDesc('is_default')
                ->orderByDesc('is_active')
                ->limit($limit)
                ->get()
                ->map(fn (PaymentIntegration $integration) => [
                    'title' => (string) $integration->title,
                    'subtitle' => $integration->providerLabel() . ' · ' . $integration->environmentLabel(),
                    'url' => $this->can($user, 'site_payments.update') ? route('admin.site.payments.edit', $integration) : route('admin.site.payments.index', ['q' => $query]),
                    'icon' => 'ki-filled ki-two-credit-cart',
                    'badge' => $integration->healthBadge()['label'],
                    'badge_class' => $integration->healthBadge()['class'],
                ])
                ->all());
        }

        if ($this->can($user, 'categories.view')) {
            $like = $this->like($query);
            $items = array_merge($items, Category::query()
                ->where(function (Builder $builder) use ($like) {
                    $builder
                        ->where('name', 'like', $like)
                        ->orWhere('slug', 'like', $like)
                        ->orWhere('description', 'like', $like);
                })
                ->latest('updated_at')
                ->limit($limit)
                ->get()
                ->map(fn (Category $category) => [
                    'title' => (string) $category->name,
                    'subtitle' => '/' . $category->slug,
                    'url' => $this->can($user, 'categories.update') ? route('admin.categories.edit', $category) : route('admin.categories.index', ['q' => $query]),
                    'icon' => 'ki-filled ki-document',
                    'badge' => $category->is_active ? 'Aktif' : 'Pasif',
                    'badge_class' => $category->is_active ? 'kt-badge kt-badge-sm kt-badge-light-success' : 'kt-badge kt-badge-sm kt-badge-light',
                ])
                ->all());
        }

        return $this->group('commerce-settings', 'Ticaret ve Yapılandırma', collect($items)->take($limit * 2)->values()->all());
    }

    private function libraryGroup(User $user, string $query, int $limit): ?array
    {
        $items = [];

        if ($this->can($user, 'galleries.view')) {
            $like = $this->like($query);
            $items = array_merge($items, Gallery::query()
                ->where(fn (Builder $builder) => $builder->where('name', 'like', $like)->orWhere('slug', 'like', $like)->orWhere('description', 'like', $like))
                ->latest('updated_at')
                ->limit($limit)
                ->get()
                ->map(fn (Gallery $gallery) => [
                    'title' => (string) $gallery->name,
                    'subtitle' => $gallery->description ? Str::limit(strip_tags($gallery->description), 90) : '/' . $gallery->slug,
                    'url' => $this->can($user, 'galleries.update') ? route('admin.galleries.edit', $gallery) : route('admin.galleries.index'),
                    'icon' => 'ki-filled ki-picture',
                    'badge' => 'Galeri',
                    'badge_class' => 'kt-badge kt-badge-sm kt-badge-light-primary',
                ])
                ->all());
        }

        if ($this->can($user, 'media.view')) {
            $like = $this->like($query);
            $items = array_merge($items, Media::query()
                ->where(function (Builder $builder) use ($like) {
                    $builder
                        ->where('original_name', 'like', $like)
                        ->orWhere('title', 'like', $like)
                        ->orWhere('alt', 'like', $like);
                })
                ->latest()
                ->limit($limit)
                ->get()
                ->map(fn (Media $media) => [
                    'title' => (string) ($media->title ?: $media->original_name ?: 'Medya #' . $media->id),
                    'subtitle' => trim(($media->mime_type ?: 'Dosya') . ' · ' . ($media->alt ?: 'Alt metin yok')),
                    'url' => route('admin.media.index', ['q' => $query]),
                    'icon' => $media->isImage() ? 'ki-filled ki-picture' : 'ki-filled ki-file',
                    'badge' => $media->isImage() ? 'Görsel' : 'Dosya',
                    'badge_class' => 'kt-badge kt-badge-sm kt-badge-light',
                ])
                ->all());
        }

        return $this->group('library', 'Medya ve Galeri', collect($items)->take($limit * 2)->values()->all());
    }

    private function appointmentsGroup(User $user, string $query, int $limit): ?array
    {
        if (!$this->can($user, 'appointments.view')) {
            return null;
        }

        $like = $this->like($query);
        $items = Appointment::query()
            ->with(['member:id,name,surname,email,phone', 'provider:id,name,email'])
            ->where(function (Builder $builder) use ($like) {
                $builder
                    ->where('status', 'like', $like)
                    ->orWhere('notes_internal', 'like', $like)
                    ->orWhereHas('member', function (Builder $memberQuery) use ($like) {
                        $memberQuery
                            ->where('name', 'like', $like)
                            ->orWhere('surname', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhere('phone', 'like', $like);
                    })
                    ->orWhereHas('provider', function (Builder $providerQuery) use ($like) {
                        $providerQuery
                            ->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    });
            })
            ->latest('start_at')
            ->limit($limit)
            ->get()
            ->map(fn (Appointment $appointment) => [
                'title' => ($appointment->member?->full_name ?: 'Üye yok') . ' · ' . optional($appointment->start_at)->format('d.m.Y H:i'),
                'subtitle' => 'Uzman: ' . ($appointment->provider?->name ?: '-') . ' · ' . $appointment->status,
                'url' => route('admin.appointments.calendar', [
                    'date' => optional($appointment->start_at)->format('Y-m-d'),
                ]),
                'icon' => 'ki-filled ki-calendar-8',
                'badge' => 'Randevu',
                'badge_class' => 'kt-badge kt-badge-sm kt-badge-light-warning',
            ])
            ->all();

        return $this->group('appointments', 'Randevular', $items);
    }

    private function messagesGroup(User $user, string $query, int $limit): ?array
    {
        $like = $this->like($query);
        $items = ContactMessage::query()
            ->visibleToUser($user)
            ->where(function (Builder $builder) use ($like) {
                $builder
                    ->where('subject', 'like', $like)
                    ->orWhere('message', 'like', $like)
                    ->orWhere('sender_name', 'like', $like)
                    ->orWhere('sender_surname', 'like', $like)
                    ->orWhere('sender_email', 'like', $like)
                    ->orWhere('sender_phone', 'like', $like);
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (ContactMessage $message) => [
                'title' => (string) ($message->subject ?: 'Mesaj #' . $message->id),
                'subtitle' => trim(($message->sender_full_name ?: $message->sender_email ?: 'Gönderen yok') . ' · ' . Str::limit(strip_tags((string) $message->message), 82)),
                'url' => route('admin.messages.show', $message),
                'icon' => 'ki-filled ki-messages',
                'badge' => ContactMessage::priorityLabel($message->priority),
                'badge_class' => ContactMessage::priorityBadgeClass($message->priority),
            ])
            ->all();

        return $this->group('messages', 'Mesajlar', $items);
    }

    private function shortcut(User $user, string $title, string $subtitle, string $route, string $icon, ?string $permission = null, array $roles = []): ?array
    {
        if ($permission && !$this->can($user, $permission)) {
            return null;
        }

        if ($roles && !$user->isSuperAdmin() && !$user->isAdmin()) {
            return null;
        }

        if (!Route::has($route)) {
            return null;
        }

        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'url' => route($route),
            'icon' => $icon,
            'badge' => 'Kısayol',
            'badge_class' => 'kt-badge kt-badge-sm kt-badge-light-primary',
        ];
    }

    private function flattenMenuItem(array $item): array
    {
        $current = [];
        if (!empty($item['route']) && !empty($item['title'])) {
            $current[] = [
                'title' => $item['title'],
                'route' => $item['route'],
                'icon' => $item['icon'] ?? null,
                'perm' => $item['perm'] ?? null,
                'permAny' => $item['permAny'] ?? null,
                'guard' => $item['guard'] ?? null,
            ];
        }

        foreach (($item['children'] ?? []) as $child) {
            $current = array_merge($current, $this->flattenMenuItem(array_merge([
                'icon' => $item['icon'] ?? null,
            ], $child)));
        }

        return $current;
    }

    private function menuItemAllowed(User $user, array $item): bool
    {
        if (!empty($item['route']) && !Route::has((string) $item['route'])) {
            return false;
        }

        if (!empty($item['perm'])) {
            return $this->can($user, (string) $item['perm']);
        }

        if (!empty($item['permAny']) && is_array($item['permAny'])) {
            foreach ($item['permAny'] as $permission) {
                if ($this->can($user, (string) $permission)) {
                    return true;
                }
            }

            return false;
        }

        if (($item['guard'] ?? null) === 'admin') {
            return $user->canAccessAdmin();
        }

        return true;
    }

    private function can(User $user, string $permission): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission($permission);
    }

    private function group(string $key, string $label, array $items): ?array
    {
        if (count($items) === 0) {
            return null;
        }

        return [
            'key' => $key,
            'label' => $label,
            'items' => array_values($items),
        ];
    }

    private function like(string $query): string
    {
        return '%' . addcslashes($query, "\\%_") . '%';
    }

    private function routeUrl(string $route): string
    {
        return Route::has($route) ? route($route) : route('admin.dashboard');
    }

    private function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }
}
