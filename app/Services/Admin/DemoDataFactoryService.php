<?php

namespace App\Services\Admin;

use App\Models\Admin\AdminNotification;
use App\Models\Admin\AuditLog\AuditLog;
use App\Models\Admin\BlogPost\BlogPost;
use App\Models\Admin\Category;
use App\Models\Admin\Ecommerce\EcommerceCoupon;
use App\Models\Admin\Ecommerce\EcommerceInvoice;
use App\Models\Admin\Ecommerce\EcommerceOrder;
use App\Models\Admin\Ecommerce\EcommerceOrderItem;
use App\Models\Admin\Ecommerce\EcommerceOrderStatusHistory;
use App\Models\Admin\Ecommerce\EcommerceOrderTransaction;
use App\Models\Admin\Ecommerce\EcommerceShipment;
use App\Models\Admin\Ecommerce\InventoryMovement;
use App\Models\Admin\Ecommerce\PaymentWebhookEvent;
use App\Models\Admin\Gallery\Gallery;
use App\Models\Admin\Media\Media;
use App\Models\Admin\Product\Product;
use App\Models\Admin\Product\ProductVariant;
use App\Models\Admin\Project\Project;
use App\Models\Admin\Project\ProjectFile;
use App\Models\Admin\User\Permission;
use App\Models\Admin\User\Role;
use App\Models\Admin\User\User;
use App\Models\Appointment\Appointment;
use App\Models\Appointment\AppointmentSlot;
use App\Models\Appointment\GlobalBlackout;
use App\Models\Appointment\ProviderTimeOff;
use App\Models\Appointment\ProviderWorkingHour;
use App\Models\ContactMessage;
use App\Models\Member;
use App\Models\Review\ServiceReview;
use App\Models\Review\ServiceReviewItem;
use App\Models\Review\ServiceReviewQuestion;
use App\Models\Site\HomeSlider;
use App\Models\Site\PaymentIntegration;
use App\Models\Site\SiteCounter;
use App\Models\Site\SiteFaq;
use App\Models\Site\SiteHomepageConfig;
use App\Models\Site\SiteHomepageSection;
use App\Models\Site\SitePage;
use App\Models\Site\SiteSetting;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DemoDataFactoryService
{
    public const DEMO_PASSWORD = 'Demo123!';

    private const LOCK_KEY = 'admin:demo-data-factory';

    private const RESET_TABLES = [
        'service_review_items',
        'service_reviews',
        'service_review_questions',
        'project_files',
        'appointment_slots',
        'appointments',
        'provider_working_hours',
        'provider_time_offs',
        'global_blackouts',
        'contact_messages',
        'payment_webhook_events',
        'ecommerce_invoices',
        'inventory_movements',
        'product_variants',
        'ecommerce_order_status_histories',
        'ecommerce_shipments',
        'ecommerce_order_transactions',
        'ecommerce_order_items',
        'ecommerce_orders',
        'ecommerce_coupons',
        'admin_notifications',
        'audit_logs',
        'site_homepage_section_item_translations',
        'site_homepage_section_items',
        'site_homepage_section_translations',
        'site_homepage_sections',
        'home_slider_translations',
        'home_sliders',
        'site_navigation_item_translations',
        'site_navigation_items',
        'site_counter_translations',
        'site_counters',
        'site_faq_translations',
        'site_faqs',
        'site_page_translations',
        'site_pages',
        'blog_post_translations',
        'project_translations',
        'product_translations',
        'category_translations',
        'galleryables',
        'gallery_items',
        'galleries',
        'mediables',
        'category_product',
        'categorizables',
        'blog_posts',
        'projects',
        'products',
        'categories',
        'member_password_reset_tokens',
        'members',
    ];

    public function overview(): array
    {
        $modules = [
            $this->module('panel_users', 'Uzman kullanıcılar', 'ki-filled ki-profile-user', $this->nonAdminUserCount()),
            $this->module('members', 'Üyeler', 'ki-filled ki-people', $this->countTable('members')),
            $this->module('blog_posts', 'Blog yazıları', 'ki-filled ki-notepad-edit', $this->countTable('blog_posts')),
            $this->module('projects', 'Projeler', 'ki-filled ki-briefcase', $this->countTable('projects')),
            $this->module('products', 'Ürünler', 'ki-filled ki-handcart', $this->countTable('products')),
            $this->module('media', 'Medya ve galeriler', 'ki-filled ki-picture', $this->countTable('media') + $this->countTable('galleries')),
            $this->module('appointments', 'Randevular', 'ki-filled ki-calendar-8', $this->countTable('appointments')),
            $this->module('contact_messages', 'İletişim mesajları', 'ki-filled ki-messages', $this->countTable('contact_messages')),
            $this->module('orders', 'Sipariş ve stok', 'ki-filled ki-basket', $this->countTable('ecommerce_orders') + $this->countTable('inventory_movements')),
            $this->module('site_content', 'Sayfa, SSS ve vitrin', 'ki-filled ki-element-11', $this->siteContentCount()),
            $this->module('reviews', 'Değerlendirmeler', 'ki-filled ki-star', $this->countTable('service_reviews')),
            $this->module('system_activity', 'Bildirim ve loglar', 'ki-filled ki-notification-status', $this->countTable('admin_notifications') + $this->countTable('audit_logs')),
        ];

        return [
            'modules' => $modules,
            'resettable_total' => collect($modules)->sum('count'),
            'protected' => [
                'users' => $this->protectedUserIds()->count(),
                'roles' => $this->countTable('roles'),
                'permissions' => $this->countTable('permissions'),
                'site_settings' => $this->countTable('site_settings') + $this->countTable('site_homepage_configs'),
                'payment_integrations' => $this->countTable('payment_integrations'),
            ],
            'demo_accounts' => User::query()
                ->where('email', 'like', 'demo.%@example.test')
                ->latest('id')
                ->limit(6)
                ->get(['name', 'email']),
        ];
    }

    public function generate(User $actor): array
    {
        return $this->runLocked(function () use ($actor): array {
            $token = now()->format('ymdHis').'-'.Str::lower(Str::random(4));
            $beforeTotal = (int) $this->overview()['resettable_total'];

            try {
                DB::transaction(function () use ($actor, $token): void {
                    $providers = $this->createProviders($token);
                    $members = $this->createMembers($token);
                    $categories = $this->createCategories($token);
                    $media = $this->createMedia($token);
                    $galleries = $this->createGalleries($token, $actor, $media);
                    $blogs = $this->createBlogPosts($token, $actor, $categories, $media, $galleries);
                    $products = $this->createProducts($token, $actor, $categories, $media, $galleries);
                    $appointments = $this->createAppointments($providers, $members, $actor);
                    $projects = $this->createProjects($token, $members, $appointments, $categories, $media, $galleries);

                    $this->createContactMessages($providers, $members);
                    $this->createSiteContent($token, $media);
                    $this->createCommerceData($token, $actor, $members, $products);
                    $this->createReviews($providers, $members, $appointments, $projects);
                    $this->createSystemActivity($actor, $providers, $blogs->count());
                    $this->createTrashScenarios($blogs, $products, $projects, $galleries, $media);
                }, 3);
            } catch (Throwable $exception) {
                Storage::disk('public')->deleteDirectory('demo-data/'.$token);
                Storage::disk('local')->deleteDirectory('demo-data/'.$token);
                throw $exception;
            }

            $overview = $this->overview();

            return [
                'token' => $token,
                'created_total' => max(0, (int) $overview['resettable_total'] - $beforeTotal),
                'overview' => $overview,
            ];
        });
    }

    public function reset(User $actor): array
    {
        return $this->runLocked(function () use ($actor): array {
            $protectedUserIds = $this->protectedUserIds()->push($actor->id)->unique()->values();
            if ($protectedUserIds->isEmpty()) {
                throw new RuntimeException('Korunacak yönetici hesabı bulunamadı. Sıfırlama iptal edildi.');
            }

            $protectedMediaIds = $this->protectedMediaIds($protectedUserIds);
            $files = $this->filesScheduledForDeletion($protectedMediaIds);
            $removed = 0;

            Schema::disableForeignKeyConstraints();

            try {
                DB::transaction(function () use ($protectedUserIds, $protectedMediaIds, &$removed): void {
                    foreach (self::RESET_TABLES as $table) {
                        if (Schema::hasTable($table)) {
                            $removed += DB::table($table)->delete();
                        }
                    }

                    if (Schema::hasTable('media_translations')) {
                        $removed += $this->deleteExcept('media_translations', 'media_id', $protectedMediaIds);
                    }

                    if (Schema::hasTable('media')) {
                        $removed += $this->deleteExcept('media', 'id', $protectedMediaIds);
                    }

                    if (Schema::hasTable('admin_dashboard_preferences')) {
                        $removed += $this->deleteExcept('admin_dashboard_preferences', 'user_id', $protectedUserIds);
                    }

                    if (Schema::hasTable('role_user')) {
                        $removed += $this->deleteExcept('role_user', 'user_id', $protectedUserIds);
                    }

                    if (Schema::hasTable('users')) {
                        $removed += $this->deleteExcept('users', 'id', $protectedUserIds);
                    }

                    $this->cleanAuthenticationState($protectedUserIds);
                    $this->detachDeletedMenuEditor($protectedUserIds);
                }, 3);
            } finally {
                Schema::enableForeignKeyConstraints();
            }

            $this->deletePhysicalFiles($files);
            Cache::flush();

            return [
                'removed_total' => $removed,
                'protected_users' => $protectedUserIds->count(),
                'protected_media' => $protectedMediaIds->count(),
            ];
        });
    }

    private function createProviders(string $token): Collection
    {
        $role = Role::query()->firstOrCreate(
            ['slug' => 'provider'],
            ['name' => 'Uzman', 'priority' => 200]
        );
        $permissionIds = Permission::query()
            ->whereIn('slug', ['messages.view', 'messages.update', 'appointments.view', 'service_reviews.view'])
            ->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            $role->permissions()->syncWithoutDetaching($permissionIds->all());
        }

        $profiles = [
            ['Ece Yılmaz', 'Veri Analizi Uzmanı', ['Raporlama', 'SPSS', 'İstatistik']],
            ['Mert Kaya', 'Araştırma Danışmanı', ['Araştırma Tasarımı', 'Power BI', 'Anket']],
            ['Selin Arslan', 'Proje Koordinatörü', ['Proje Yönetimi', 'Süreç Tasarımı', 'Danışmanlık']],
        ];

        return collect($profiles)->map(function (array $profile, int $index) use ($token, $role): User {
            $user = User::query()->create([
                'name' => $profile[0],
                'title' => $profile[1],
                'email' => 'demo.uzman'.($index + 1).'.'.$token.'@example.test',
                'phone' => '0555 100 2'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'company' => 'PROBABLUE Demo',
                'location' => ['İstanbul', 'Ankara', 'İzmir'][$index],
                'bio' => 'Örnek projelerde müşteri ihtiyaçlarını analiz eden demo uzman profili.',
                'skills' => $profile[2],
                'password' => Hash::make(self::DEMO_PASSWORD),
                'is_active' => true,
            ]);
            $user->roles()->attach($role->id);
            $this->spreadCreatedAt($user, 80 - ($index * 12));

            return $user;
        });
    }

    private function createMembers(string $token): Collection
    {
        $names = [
            ['Deniz', 'Acar'], ['Burak', 'Demir'], ['Zeynep', 'Koç'], ['Can', 'Öztürk'],
            ['Elif', 'Şahin'], ['Onur', 'Yıldız'], ['Seda', 'Aksoy'], ['Kerem', 'Çetin'],
        ];

        return collect($names)->map(function (array $name, int $index) use ($token): Member {
            $member = Member::query()->create([
                'name' => $name[0],
                'surname' => $name[1],
                'email' => 'demo.uye'.($index + 1).'.'.$token.'@example.test',
                'phone' => '0532 400 '.str_pad((string) (1200 + $index), 4, '0', STR_PAD_LEFT),
                'institution' => ['Marmara Üniversitesi', 'İstanbul Üniversitesi', 'Ankara Üniversitesi', 'Ege Üniversitesi'][$index % 4],
                'password' => Hash::make(self::DEMO_PASSWORD),
                'email_verified_at' => $index === 6 ? null : now()->subDays(40 - $index),
                'membership_terms_accepted_at' => $index === 6 ? null : now()->subDays(40 - $index),
                'membership_terms_version' => '2026.1',
                'last_login_at' => now()->subDays($index),
                'is_active' => $index !== 7,
                'suspended_at' => $index === 7 ? now()->subDays(2) : null,
                'suspension_reason' => $index === 7 ? 'Örnek askıya alma senaryosu' : null,
            ]);

            if ($index < 2) {
                $path = 'demo-data/'.$token.'/members/member-'.($index + 1).'.txt';
                $contents = "PROBABLUE örnek üye dokümanı\nÜye: {$member->full_name}\n";
                Storage::disk('local')->put($path, $contents);
                $member->forceFill([
                    'filepath' => $path,
                    'file_disk' => 'local',
                    'file_original_name' => 'ornek-uye-belgesi.txt',
                    'file_mime_type' => 'text/plain',
                    'file_size' => strlen($contents),
                ])->save();
            }

            $this->spreadCreatedAt($member, 70 - ($index * 7));

            return $member;
        });
    }

    private function createCategories(string $token): Collection
    {
        $items = [
            ['Veri Analizi', 'İstatistiksel analiz, raporlama ve içgörü içerikleri.'],
            ['Araştırma', 'Akademik ve kurumsal araştırma süreçleri.'],
            ['Danışmanlık', 'Strateji ve proje danışmanlığı hizmetleri.'],
            ['Eğitim', 'Atölye, eğitim ve gelişim programları.'],
            ['Raporlama', 'Dashboard ve karar destek çözümleri.'],
            ['Dijital Ürünler', 'Şablon, rapor ve dijital içerik kataloğu.'],
        ];

        return collect($items)->map(fn (array $item): Category => Category::query()->create([
            'name' => $item[0],
            'slug' => Str::slug($item[0]).'-'.$token,
            'description' => $item[1],
            'is_active' => true,
        ]));
    }

    private function createMedia(string $token): Collection
    {
        $palettes = [
            ['#0f766e', '#5eead4'], ['#1d4ed8', '#93c5fd'], ['#be123c', '#fda4af'],
            ['#92400e', '#fcd34d'], ['#4338ca', '#c4b5fd'], ['#166534', '#86efac'],
            ['#9f1239', '#fbcfe8'], ['#075985', '#7dd3fc'], ['#3f3f46', '#d4d4d8'],
            ['#7c2d12', '#fdba74'], ['#155e75', '#67e8f9'], ['#4c1d95', '#ddd6fe'],
        ];

        return collect($palettes)->map(function (array $palette, int $index) use ($token): Media {
            $path = 'demo-data/'.$token.'/media/visual-'.($index + 1).'.svg';
            Storage::disk('public')->put($path, $this->demoSvg($palette[0], $palette[1], $index + 1));

            return Media::query()->create([
                'uuid' => (string) Str::uuid(),
                'disk' => 'public',
                'path' => $path,
                'variants' => ['original' => $path, 'optimized' => $path, 'thumb' => $path],
                'original_name' => 'probablue-demo-'.($index + 1).'.svg',
                'mime_type' => 'image/svg+xml',
                'size' => Storage::disk('public')->size($path),
                'width' => 1280,
                'height' => 800,
                'title' => 'Örnek görsel '.($index + 1),
                'alt' => 'PROBABLUE örnek içerik görseli '.($index + 1),
                'meta' => ['source' => 'demo-data-factory', 'batch' => $token],
            ]);
        });
    }

    private function createGalleries(string $token, User $actor, Collection $media): Collection
    {
        $definitions = [
            ['Analiz ve Dashboard Çalışmaları', 'Tamamlanan örnek analiz ekranları ve rapor görselleri.'],
            ['Eğitim ve Atölye Galerisi', 'Eğitim süreçlerinden örnek sunum ve çalışma alanları.'],
        ];

        return collect($definitions)->map(function (array $definition, int $index) use ($token, $actor, $media): Gallery {
            $gallery = Gallery::query()->create([
                'name' => $definition[0],
                'slug' => Str::slug($definition[0]).'-'.$token,
                'description' => $definition[1],
                'is_public' => true,
                'published_at' => now()->subDays(12 - ($index * 4)),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $media->slice($index * 4, 4)->values()->each(function (Media $item, int $position) use ($gallery): void {
                $gallery->items()->create([
                    'media_id' => $item->id,
                    'sort_order' => $position + 1,
                    'caption' => 'Örnek çalışma görseli '.($position + 1),
                    'alt' => $item->alt,
                    'link_target' => '_self',
                ]);
            });

            return $gallery;
        });
    }

    private function createBlogPosts(
        string $token,
        User $actor,
        Collection $categories,
        Collection $media,
        Collection $galleries
    ): Collection {
        $titles = [
            'Veriye Dayalı Karar Vermenin 7 Temel Adımı',
            'Araştırma Sorusu Nasıl Güçlendirilir?',
            'Dashboard Tasarımında Okunabilirlik Rehberi',
            'Anket Verisinde Kalite Kontrol Listesi',
            'İstatistiksel Analiz Projesi Nasıl Planlanır?',
            'KPI Seçerken Yapılan Yaygın Hatalar',
            'Raporlamada Hikâyeleştirme Teknikleri',
            '2026 Veri Analitiği Eğilimleri',
        ];

        return collect($titles)->map(function (string $title, int $index) use ($token, $actor, $categories, $media, $galleries): BlogPost {
            $published = $index !== 6;
            $post = BlogPost::query()->create([
                'title' => $title,
                'slug' => Str::slug($title).'-'.$token,
                'excerpt' => 'Bu örnek içerik, '.$title.' konusunda uygulanabilir adımları ve iyi pratikleri özetler.',
                'content' => '<h2>Başlangıç</h2><p>Doğru planlama, güvenilir veri ve açık iletişim başarılı bir çalışma için birlikte ele alınmalıdır.</p><h3>Uygulama adımları</h3><ul><li>Hedefi ve başarı ölçütünü netleştirin.</li><li>Veri kalitesini sistematik biçimde kontrol edin.</li><li>Bulguları karar vericinin diline çevirin.</li></ul><p>PROBABLUE danışmanlık yaklaşımı, teknik doğruluk ile anlaşılır sunumu aynı süreçte buluşturur.</p>',
                'is_published' => $published,
                'published_at' => $published ? now()->subDays(48 - ($index * 6)) : null,
                'is_featured' => $index < 2,
                'featured_at' => $index < 2 ? now()->subDays($index + 1) : null,
                'meta_title' => $title.' | PROBABLUE',
                'meta_description' => 'PROBABLUE örnek blog içeriği: '.$title,
                'meta_keywords' => 'veri analizi, danışmanlık, araştırma, raporlama',
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
            $post->categories()->attach($categories[$index % $categories->count()]->id);
            $post->featuredMedia()->attach($media[$index % $media->count()]->id, ['collection' => 'featured', 'order' => 1]);

            if ($index < $galleries->count()) {
                $post->galleries()->attach($galleries[$index]->id, ['slot' => 'main', 'sort_order' => 1]);
            }

            $this->spreadCreatedAt($post, 55 - ($index * 6));

            return $post;
        });
    }

    private function createProducts(
        string $token,
        User $actor,
        Collection $categories,
        Collection $media,
        Collection $galleries
    ): Collection {
        $products = [
            ['İleri Veri Analizi Paketi', 14900, 18],
            ['Araştırma Tasarımı Danışmanlığı', 9800, 7],
            ['Power BI Dashboard Şablonu', 2450, 42],
            ['SPSS Analiz Kontrol Listesi', 790, 120],
            ['Kurumsal KPI Atölyesi', 12500, 5],
            ['Akademik Raporlama Paketi', 7200, 14],
            ['Anket Tasarım Kiti', 1650, 28],
            ['Yönetici Raporu Şablonu', 1100, 3],
        ];

        return collect($products)->map(function (array $definition, int $index) use ($token, $actor, $categories, $media, $galleries): Product {
            $price = (float) $definition[1];
            $product = Product::query()->create([
                'title' => $definition[0],
                'slug' => Str::slug($definition[0]).'-'.$token,
                'content' => '<p>Profesyonel analiz ve danışmanlık süreçlerinde kullanılmak üzere hazırlanmış örnek ürün açıklamasıdır.</p><ul><li>Net kapsam</li><li>Ölçülebilir çıktı</li><li>Uzman desteği</li></ul>',
                'sku' => 'DEMO-'.$token.'-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'barcode' => '869'.now()->format('ymd').str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'price' => $price,
                'sale_price' => $index % 3 === 0 ? round($price * 0.88, 2) : null,
                'stock' => $definition[2],
                'currency' => 'TRY',
                'vat_rate' => 20,
                'brand' => 'PROBABLUE',
                'weight' => 0.250,
                'width' => 24,
                'height' => 3,
                'length' => 32,
                'is_active' => true,
                'sort_order' => $index + 1,
                'status' => $index === 6 ? Product::STATUS_DRAFT : Product::STATUS_ACTIVE,
                'is_featured' => $index < 3,
                'featured_at' => $index < 3 ? now()->subDays($index) : null,
                'meta_title' => $definition[0].' | PROBABLUE',
                'meta_description' => $definition[0].' için örnek ürün ve hizmet içeriği.',
                'meta_keywords' => 'analiz, danışmanlık, raporlama, eğitim',
            ]);
            $product->categories()->attach($categories[($index + 2) % $categories->count()]->id);
            $product->featuredMedia()->attach($media[($index + 3) % $media->count()]->id, ['collection' => 'featured', 'order' => 1]);

            if ($index < $galleries->count()) {
                $product->galleries()->attach($galleries[$index]->id, ['slot' => 'main', 'sort_order' => 1]);
            }

            foreach (['Standart', 'Plus'] as $variantIndex => $variantName) {
                $stock = max(1, (int) $definition[2] - ($variantIndex * 3));
                $variant = ProductVariant::query()->create([
                    'product_id' => $product->id,
                    'title' => $variantName,
                    'sku' => $product->sku.'-'.($variantIndex + 1),
                    'option_values' => ['Paket' => $variantName],
                    'price' => $price + ($variantIndex * 850),
                    'currency' => 'TRY',
                    'stock' => $stock,
                    'low_stock_threshold' => 5,
                    'is_active' => true,
                    'sort_order' => $variantIndex + 1,
                ]);
                InventoryMovement::query()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'user_id' => $actor->id,
                    'type' => InventoryMovement::TYPE_IN,
                    'reason' => 'Örnek açılış stoğu',
                    'quantity' => $stock,
                    'before_stock' => 0,
                    'after_stock' => $stock,
                    'reference' => 'DEMO-'.$token,
                    'occurred_at' => now()->subDays(20 - $index),
                ]);
            }

            $this->spreadCreatedAt($product, 45 - ($index * 5));

            return $product;
        });
    }

    private function createAppointments(Collection $providers, Collection $members, User $actor): Collection
    {
        $providers->each(function (User $provider): void {
            foreach (range(1, 5) as $day) {
                ProviderWorkingHour::query()->create([
                    'provider_id' => $provider->id,
                    'day_of_week' => $day,
                    'is_enabled' => true,
                    'start_time' => '09:00:00',
                    'end_time' => $day === 5 ? '16:00:00' : '18:00:00',
                ]);
            }
        });

        ProviderTimeOff::query()->create([
            'provider_id' => $providers->first()->id,
            'start_at' => now('UTC')->addDays(12)->setTime(13, 0),
            'end_at' => now('UTC')->addDays(12)->setTime(17, 0),
            'reason' => 'Örnek kurum içi eğitim',
            'block_type' => 'manual',
        ]);
        GlobalBlackout::query()->create([
            'label' => 'Örnek bakım aralığı',
            'start_at' => now('UTC')->addDays(20)->setTime(12, 0),
            'end_at' => now('UTC')->addDays(20)->setTime(15, 0),
        ]);

        return collect(range(0, 11))->map(function (int $index) use ($providers, $members, $actor): Appointment {
            $start = now('UTC')->startOfDay()->addDays($index - 6)->addHours(9 + (($index % 4) * 2));
            $status = match (true) {
                $index < 4 => Appointment::STATUS_COMPLETED,
                $index === 4 => Appointment::STATUS_CANCELLED_BY_MEMBER,
                $index === 5 => Appointment::STATUS_NO_SHOW,
                default => Appointment::STATUS_BOOKED,
            };
            $appointment = Appointment::query()->create([
                'provider_id' => $providers[$index % $providers->count()]->id,
                'member_id' => $members[$index % $members->count()]->id,
                'start_at' => $start,
                'end_at' => $start->copy()->addHour(),
                'blocks' => 2,
                'status' => $status,
                'notes_internal' => 'Örnek randevu: ihtiyaç analizi ve çalışma kapsamı görüşmesi.',
                'cancelled_at' => $status === Appointment::STATUS_CANCELLED_BY_MEMBER ? $start->copy()->subDay() : null,
                'cancel_reason' => $status === Appointment::STATUS_CANCELLED_BY_MEMBER ? 'Program değişikliği' : null,
                'created_by_user_id' => $actor->id,
            ]);
            AppointmentSlot::query()->create([
                'appointment_id' => $appointment->id,
                'provider_id' => $appointment->provider_id,
                'slot_start_at' => $start,
            ]);

            return $appointment;
        });
    }

    private function createProjects(
        string $token,
        Collection $members,
        Collection $appointments,
        Collection $categories,
        Collection $media,
        Collection $galleries
    ): Collection {
        $titles = [
            'Müşteri Memnuniyeti Analizi', 'Satış Performans Dashboardu', 'Akademik Araştırma Modeli',
            'Çalışan Deneyimi Raporu', 'Pazar Segmentasyonu', 'KPI Dönüşüm Projesi',
        ];
        $statuses = [
            Project::STATUS_APPOINTMENT_DONE,
            Project::STATUS_DEV_IN_PROGRESS,
            Project::STATUS_DELIVERED,
            Project::STATUS_APPROVED,
            Project::STATUS_ACTIVE,
            Project::STATUS_DRAFT,
        ];

        return collect($titles)->map(function (string $title, int $index) use ($token, $members, $appointments, $categories, $media, $galleries, $statuses): Project {
            $project = Project::query()->create([
                'title' => $title,
                'slug' => Str::slug($title).'-'.$token,
                'content' => '<h2>Proje özeti</h2><p>Bu örnek proje, veri toplama, kalite kontrol, analiz ve yönetici raporlaması aşamalarını kapsar.</p><h3>Beklenen çıktılar</h3><ul><li>Doğrulanmış veri seti</li><li>Yönetici özeti</li><li>Aksiyon önerileri</li></ul>',
                'meta_title' => $title.' | PROBABLUE',
                'meta_description' => $title.' örnek proje akışı ve teslimat kapsamı.',
                'meta_keywords' => 'proje, veri analizi, danışmanlık',
                'status' => $statuses[$index],
                'is_featured' => $index < 3,
                'featured_at' => $index < 3 ? now()->subDays($index + 2) : null,
                'appointment_id' => $appointments[$index]->id,
                'member_id' => $members[$index]->id,
            ]);
            $project->categories()->attach($categories[$index % $categories->count()]->id);
            $project->featuredMedia()->attach($media[($index + 5) % $media->count()]->id, ['collection' => 'featured', 'order' => 1]);
            if ($index < $galleries->count()) {
                $project->galleries()->attach($galleries[$index]->id, ['slot' => 'main', 'sort_order' => 1]);
            }

            if ($index < 3) {
                $path = 'demo-data/'.$token.'/projects/project-'.($index + 1).'.txt';
                $contents = "PROBABLUE örnek proje dosyası\nProje: {$title}\n";
                Storage::disk('local')->put($path, $contents);
                ProjectFile::query()->create([
                    'project_id' => $project->id,
                    'member_id' => $project->member_id,
                    'disk' => 'local',
                    'path' => $path,
                    'original_name' => Str::slug($title).'-notlar.txt',
                    'mime_type' => 'text/plain',
                    'size' => strlen($contents),
                    'note' => 'Üye tarafından yüklenen örnek proje notu.',
                ]);
            }

            $this->spreadCreatedAt($project, 38 - ($index * 6));

            return $project;
        });
    }

    private function createContactMessages(Collection $providers, Collection $members): void
    {
        $subjects = [
            'Analiz desteği hakkında', 'Kurumsal raporlama talebi', 'Randevu öncesi bilgi',
            'Dashboard güncelleme isteği', 'Araştırma yöntemi danışmanlığı', 'Eğitim teklifi',
            'Proje teslim tarihi', 'Veri güvenliği sorusu', 'Fiyatlandırma bilgisi',
            'Yeni çalışma kapsamı', 'Rapor revizyon talebi', 'Acil analiz desteği',
        ];
        $priorities = [ContactMessage::PRIORITY_NORMAL, ContactMessage::PRIORITY_HIGH, ContactMessage::PRIORITY_LOW, ContactMessage::PRIORITY_URGENT];
        $statuses = [
            ContactMessage::STATUS_NEW, ContactMessage::STATUS_OPEN, ContactMessage::STATUS_IN_PROGRESS,
            ContactMessage::STATUS_WAITING, ContactMessage::STATUS_RESOLVED, ContactMessage::STATUS_CLOSED,
        ];

        foreach ($subjects as $index => $subject) {
            $provider = $providers[$index % $providers->count()];
            $isMember = $index % 2 === 0;
            $member = $isMember ? $members[$index % $members->count()] : null;
            $status = $statuses[$index % count($statuses)];
            $message = ContactMessage::query()->create([
                'recipient_user_id' => $provider->id,
                'assigned_user_id' => in_array($status, [ContactMessage::STATUS_IN_PROGRESS, ContactMessage::STATUS_RESOLVED, ContactMessage::STATUS_CLOSED], true) ? $provider->id : null,
                'member_id' => $member?->id,
                'recipient_name' => $provider->name,
                'sender_type' => $isMember ? ContactMessage::SENDER_TYPE_MEMBER : ContactMessage::SENDER_TYPE_GUEST,
                'sender_name' => $member?->name ?? ['Ayşe', 'Mehmet', 'Ceren', 'Umut', 'Pelin', 'Tolga'][$index % 6],
                'sender_surname' => $member?->surname ?? 'Ziyaretçi',
                'sender_email' => $member?->email ?? 'ziyaretci'.($index + 1).'@example.test',
                'sender_phone' => $member?->phone ?? '0555 700 '.str_pad((string) (1300 + $index), 4, '0', STR_PAD_LEFT),
                'preferred_channels' => $isMember ? [ContactMessage::CONTACT_CHANNEL_EMAIL, ContactMessage::CONTACT_CHANNEL_PHONE] : [ContactMessage::CONTACT_CHANNEL_EMAIL],
                'tags' => [$index % 3 === 0 ? 'teklif' : 'destek', $index % 4 === 0 ? 'takip' : 'bilgi'],
                'subject' => $subject,
                'priority' => $priorities[$index % count($priorities)],
                'status' => $status,
                'message' => 'Örnek iletişim mesajıdır. İhtiyacımızı değerlendirip uygun çalışma planı ve sonraki adımlar hakkında bilgi paylaşabilir misiniz?',
                'internal_note' => $index % 3 === 0 ? 'İlk görüşmede kapsam ve veri formatı netleştirilecek.' : null,
                'resolution_note' => in_array($status, [ContactMessage::STATUS_RESOLVED, ContactMessage::STATUS_CLOSED], true) ? 'Talep yanıtlandı ve gerekli dokümanlar paylaşıldı.' : null,
                'read_at' => $status !== ContactMessage::STATUS_NEW ? now()->subDays(max(1, 11 - $index)) : null,
                'due_at' => now()->addDays(($index % 5) - 2),
                'first_response_at' => $status !== ContactMessage::STATUS_NEW ? now()->subDays(max(1, 10 - $index)) : null,
                'resolved_at' => in_array($status, [ContactMessage::STATUS_RESOLVED, ContactMessage::STATUS_CLOSED], true) ? now()->subDays(2) : null,
                'closed_at' => $status === ContactMessage::STATUS_CLOSED ? now()->subDay() : null,
                'closed_by_user_id' => $status === ContactMessage::STATUS_CLOSED ? $provider->id : null,
                'last_activity_at' => now()->subDays(max(0, 11 - $index)),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PROBABLUE Demo Data Factory',
            ]);
            $this->spreadCreatedAt($message, 65 - ($index * 5));
        }
    }

    private function createSiteContent(string $token, Collection $media): void
    {
        $about = SitePage::query()->create([
            'title' => 'Hakkımızda',
            'slug' => 'hakkimizda-'.$token,
            'hero_kicker' => 'PROBABLUE yaklaşımı',
            'excerpt' => 'Veriyi anlaşılır kararlara dönüştüren uzman analiz ve danışmanlık ekibi.',
            'content' => '<h2>Analizden aksiyona</h2><p>İstatistiksel doğruluğu, açık iletişimi ve sürdürülebilir proje yönetimini aynı çalışma modelinde buluşturuyoruz.</p><h3>Nasıl çalışıyoruz?</h3><p>İhtiyacı dinliyor, kapsamı birlikte netleştiriyor ve her aşamayı ölçülebilir çıktılarla yönetiyoruz.</p>',
            'icon_class' => 'ki-filled ki-chart-line-up-2',
            'featured_media_id' => $media[8]->id,
            'meta_title' => 'Hakkımızda | PROBABLUE',
            'meta_description' => 'PROBABLUE istatistiksel analiz ve danışmanlık yaklaşımı.',
            'show_faqs' => true,
            'show_counters' => true,
            'is_featured' => true,
            'is_active' => true,
            'sort_order' => 1,
            'published_at' => now()->subMonth(),
        ]);
        $services = SitePage::query()->create([
            'title' => 'Hizmetlerimiz',
            'slug' => 'hizmetlerimiz-'.$token,
            'hero_kicker' => 'Uçtan uca destek',
            'excerpt' => 'Araştırma tasarımından yönetici raporuna kadar kapsamlı veri hizmetleri.',
            'content' => '<h2>İhtiyacınıza uygun çalışma modeli</h2><p>Analiz, raporlama, dashboard, araştırma danışmanlığı ve eğitim hizmetlerini tek çatı altında sunuyoruz.</p>',
            'icon_class' => 'ki-filled ki-abstract-22',
            'featured_media_id' => $media[9]->id,
            'meta_title' => 'Hizmetler | PROBABLUE',
            'meta_description' => 'Analiz, raporlama, araştırma ve danışmanlık hizmetleri.',
            'show_faqs' => true,
            'show_counters' => false,
            'is_featured' => true,
            'is_active' => true,
            'sort_order' => 2,
            'published_at' => now()->subMonth(),
        ]);

        $faqs = [
            [$about, 'Çalışma süreci nasıl başlıyor?', 'Kısa bir ihtiyaç görüşmesinin ardından kapsam, takvim ve teslimatlar yazılı olarak netleştirilir.'],
            [$about, 'Verilerim güvende mi?', 'Erişim, saklama ve paylaşım adımları proje başında belirlenen güvenlik kurallarına göre yürütülür.'],
            [$services, 'Hangi analiz programlarını kullanıyorsunuz?', 'Projenin ihtiyacına göre SPSS, R, Python, Excel ve Power BI gibi araçlar kullanılabilir.'],
            [$services, 'Teslim süresi ne kadar?', 'Veri yapısı ve kapsam incelendikten sonra gerçekçi bir iş takvimi paylaşılır.'],
            [null, 'Online danışmanlık veriyor musunuz?', 'Evet, görüşme ve proje takip süreçleri tamamen çevrim içi yürütülebilir.'],
            [null, 'Revizyon hakkı var mı?', 'Kapsam dahilindeki bulgu ve sunum revizyonları çalışma planında açıkça belirtilir.'],
            [null, 'Kurumsal eğitim düzenliyor musunuz?', 'Evet, kurumun veri olgunluğuna ve ekip hedeflerine göre özel eğitim içerikleri hazırlanır.'],
            [null, 'Nasıl teklif alabilirim?', 'İletişim formundan uzman seçerek konu, öncelik ve ihtiyacınızı iletmeniz yeterlidir.'],
        ];
        foreach ($faqs as $index => [$page, $question, $answer]) {
            SiteFaq::query()->create([
                'site_page_id' => $page?->id,
                'group_label' => $page?->title ?? 'Genel',
                'question' => $question,
                'answer' => $answer,
                'icon_class' => 'ki-filled ki-questionnaire-tablet',
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }

        foreach ([
            ['Tamamlanan proje', 148, null, '+'],
            ['Memnuniyet oranı', 96, '%', null],
            ['Analiz saati', 3200, '+', null],
            ['Aktif sektör', 18, null, '+'],
        ] as $index => $counter) {
            SiteCounter::query()->create([
                'site_page_id' => $about->id,
                'label' => $counter[0],
                'value' => $counter[1],
                'prefix' => $counter[2],
                'suffix' => $counter[3],
                'description' => 'Örnek sayaç verisi',
                'icon_class' => 'ki-filled ki-chart-simple-3',
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }

        foreach ([
            ['Veriden karara güvenli yolculuk', 'Analiz, raporlama ve danışmanlık desteğini tek ekipten alın.'],
            ['Araştırmanızı güçlü bir modele dönüştürün', 'Doğru yöntem, temiz veri ve anlaşılır bulgularla ilerleyin.'],
            ['Yönetici dashboardunuzu birlikte tasarlayalım', 'Karmaşık göstergeleri hızlı okunur bir karar ekranında birleştirin.'],
        ] as $index => $slider) {
            HomeSlider::query()->create([
                'badge' => $index === 0 ? 'İstatistiksel Analiz ve Danışmanlık' : 'PROBABLUE',
                'title' => $slider[0],
                'subtitle' => $slider[1],
                'body' => 'Örnek slider içeriği panelden düzenlenebilir.',
                'cta_label' => $index === 2 ? 'Projeleri incele' : 'İletişime geç',
                'cta_url' => $index === 2 ? '/projeler' : '/iletisim',
                'image_media_id' => $media[$index + 6]->id,
                'overlay_strength' => 48,
                'theme' => [HomeSlider::THEME_DARK, HomeSlider::THEME_BRAND, HomeSlider::THEME_LIGHT][$index],
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }

        $section = SiteHomepageSection::query()->create([
            'type' => 'features',
            'eyebrow' => 'Neden PROBABLUE?',
            'title' => 'Veriye güven, süreci birlikte yönet',
            'description' => 'Teknik doğruluk ile güçlü iletişimi bir araya getiren çalışma yaklaşımı.',
            'settings' => ['columns' => 3, 'surface' => 'muted'],
            'is_active' => true,
            'sort_order' => 10,
        ]);
        foreach ([
            ['Müşteri Memnuniyeti', 'İhtiyacınızı doğru anlayıp her aşamada açık iletişim kurarız.', 'heart'],
            ['Esnek Çalışma', 'Takvimi ve teslimatları değişen önceliklerinize göre birlikte şekillendiririz.', 'adjustments'],
            ['Şeffaf Süreç', 'Kararları veriye dayandırır, ilerlemeyi görünür kılarız.', 'chart'],
        ] as $index => $item) {
            $section->items()->create([
                'title' => $item[0],
                'description' => $item[1],
                'icon' => $item[2],
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }

    }

    private function createCommerceData(string $token, User $actor, Collection $members, Collection $products): void
    {
        foreach ([
            ['HOSGELDIN'.$token, 'Demo hoş geldin indirimi', EcommerceCoupon::TYPE_PERCENTAGE, 15],
            ['RAPOR'.$token, 'Rapor paketi indirimi', EcommerceCoupon::TYPE_FIXED, 750],
            ['KARGO'.$token, 'Demo ücretsiz teslimat', EcommerceCoupon::TYPE_FREE_SHIPPING, 0],
        ] as $index => $coupon) {
            EcommerceCoupon::query()->create([
                'code' => Str::upper(Str::replace('-', '', $coupon[0])),
                'name' => $coupon[1],
                'type' => $coupon[2],
                'value' => $coupon[3],
                'min_order_total' => 1000,
                'usage_limit' => 100,
                'usage_count' => $index * 4,
                'per_customer_limit' => 1,
                'applies_to' => ['scope' => 'all'],
                'is_active' => true,
                'starts_at' => now()->subWeek(),
                'ends_at' => now()->addMonth(),
                'notes' => 'Örnek veri fabrikası tarafından oluşturuldu.',
            ]);
        }

        $integrationId = PaymentIntegration::query()->where('is_active', true)->value('id');
        $states = [
            ['pending', 'awaiting', 'unfulfilled'],
            ['confirmed', 'paid', 'preparing'],
            ['processing', 'paid', 'preparing'],
            ['shipped', 'paid', 'fulfilled'],
            ['completed', 'paid', 'fulfilled'],
            ['cancelled', 'failed', 'cancelled'],
        ];

        foreach ($states as $index => [$status, $paymentStatus, $fulfillmentStatus]) {
            $member = $members[$index % $members->count()];
            $product = $products[$index % $products->count()];
            $subtotal = (float) ($product->sale_price ?? $product->price ?? 0);
            $tax = round($subtotal * 0.20, 2);
            $total = $subtotal + $tax;
            $isPaid = $paymentStatus === 'paid';
            $order = EcommerceOrder::query()->create([
                'order_number' => 'DEMO-'.Str::upper(Str::replace('-', '', $token)).'-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'member_id' => $member->id,
                'payment_integration_id' => $integrationId,
                'channel' => $index % 2 === 0 ? 'site' : 'admin',
                'reference_code' => 'REF-'.$token.'-'.($index + 1),
                'status' => $status,
                'payment_status' => $paymentStatus,
                'fulfillment_status' => $fulfillmentStatus,
                'customer_name' => $member->full_name,
                'customer_email' => $member->email,
                'customer_phone' => $member->phone,
                'customer_company' => $index % 2 === 0 ? 'Örnek Araştırma A.Ş.' : null,
                'currency' => 'TRY',
                'subtotal' => $subtotal,
                'tax_total' => $tax,
                'grand_total' => $total,
                'paid_total' => $isPaid ? $total : 0,
                'payment_method' => 'credit_card',
                'shipping_carrier' => in_array($status, ['shipped', 'completed'], true) ? 'Demo Kargo' : null,
                'tracking_number' => in_array($status, ['shipped', 'completed'], true) ? 'TRK'.$token.$index : null,
                'billing_address' => ['city' => 'İstanbul', 'district' => 'Kadıköy', 'line' => 'Örnek Mah. Veri Sk. No: '.($index + 1)],
                'shipping_address' => ['city' => 'İstanbul', 'district' => 'Kadıköy', 'line' => 'Örnek Mah. Veri Sk. No: '.($index + 1)],
                'customer_note' => 'Örnek sipariş müşteri notu.',
                'internal_note' => 'Veri fabrikası senaryosu.',
                'custom_fields' => ['demo_batch' => $token],
                'ordered_at' => now()->subDays(30 - ($index * 5)),
                'paid_at' => $isPaid ? now()->subDays(29 - ($index * 5)) : null,
                'shipped_at' => in_array($status, ['shipped', 'completed'], true) ? now()->subDays(5 - min($index, 4)) : null,
                'delivered_at' => $status === 'completed' ? now()->subDay() : null,
                'cancelled_at' => $status === 'cancelled' ? now()->subDays(2) : null,
            ]);
            $this->spreadCreatedAt($order, 30 - ($index * 5));

            EcommerceOrderItem::query()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_title' => $product->title,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'brand' => $product->brand,
                'quantity' => 1,
                'unit_price' => $subtotal,
                'subtotal' => $subtotal,
                'tax_rate' => 20,
                'tax_total' => $tax,
                'total' => $total,
                'currency' => 'TRY',
                'fulfillment_status' => $fulfillmentStatus,
                'custom_fields' => ['demo' => true],
            ]);
            EcommerceOrderTransaction::query()->create([
                'order_id' => $order->id,
                'payment_integration_id' => $integrationId,
                'type' => EcommerceOrderTransaction::TYPE_SALE,
                'status' => $isPaid ? EcommerceOrderTransaction::STATUS_SUCCEEDED : ($paymentStatus === 'failed' ? EcommerceOrderTransaction::STATUS_FAILED : EcommerceOrderTransaction::STATUS_PENDING),
                'amount' => $total,
                'currency' => 'TRY',
                'gateway_transaction_id' => 'DEMO-TXN-'.$token.'-'.$index,
                'processed_at' => $isPaid ? $order->paid_at : null,
                'payload' => ['demo' => true, 'batch' => $token],
            ]);
            EcommerceOrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'user_id' => $actor->id,
                'from_status' => 'draft',
                'to_status' => $status,
                'from_payment_status' => 'unpaid',
                'to_payment_status' => $paymentStatus,
                'from_fulfillment_status' => 'unfulfilled',
                'to_fulfillment_status' => $fulfillmentStatus,
                'note' => 'Örnek sipariş durum geçişi.',
            ]);

            if (in_array($status, ['processing', 'shipped', 'completed'], true)) {
                EcommerceShipment::query()->create([
                    'order_id' => $order->id,
                    'status' => $status === 'completed' ? EcommerceShipment::STATUS_DELIVERED : ($status === 'shipped' ? EcommerceShipment::STATUS_SHIPPED : EcommerceShipment::STATUS_PREPARING),
                    'carrier' => 'Demo Kargo',
                    'tracking_number' => 'DM'.$token.$index,
                    'tracking_url' => 'https://example.test/kargo/'.$token.$index,
                    'package_count' => 1,
                    'address' => $order->shipping_address,
                    'shipped_at' => $order->shipped_at,
                    'delivered_at' => $order->delivered_at,
                ]);
            }

            EcommerceInvoice::query()->create([
                'order_id' => $order->id,
                'type' => EcommerceInvoice::TYPE_INVOICE,
                'status' => $isPaid ? EcommerceInvoice::STATUS_ISSUED : EcommerceInvoice::STATUS_DRAFT,
                'currency' => 'TRY',
                'subtotal' => $subtotal,
                'tax_total' => $tax,
                'grand_total' => $total,
                'billing_snapshot' => $order->billing_address,
                'line_snapshot' => [['title' => $product->title, 'quantity' => 1, 'total' => $total]],
                'issued_at' => $isPaid ? $order->paid_at : null,
                'due_at' => now()->addDays(14),
                'notes' => 'Örnek fatura kaydı.',
            ]);

            PaymentWebhookEvent::query()->create([
                'payment_integration_id' => $integrationId,
                'order_id' => $order->id,
                'provider' => 'demo',
                'event_type' => $isPaid ? 'payment.succeeded' : 'payment.pending',
                'event_id' => 'evt-'.$token.'-'.$index,
                'status' => $isPaid ? PaymentWebhookEvent::STATUS_PROCESSED : PaymentWebhookEvent::STATUS_RECEIVED,
                'headers' => ['x-demo' => 'true'],
                'payload' => ['order_number' => $order->order_number, 'amount' => $total],
                'received_at' => $order->ordered_at,
                'processed_at' => $isPaid ? $order->paid_at : null,
            ]);
        }
    }

    private function createReviews(Collection $providers, Collection $members, Collection $appointments, Collection $projects): void
    {
        $questions = collect([
            ['Hizmet beklentinizi ne ölçüde karşıladı?', ServiceReviewQuestion::TYPE_SCALE, null, true],
            ['Süreç boyunca iletişim yeterli miydi?', ServiceReviewQuestion::TYPE_YES_NO, null, true],
            ['En güçlü bulduğunuz alan hangisiydi?', ServiceReviewQuestion::TYPE_SINGLE_CHOICE, ['Analiz', 'İletişim', 'Raporlama', 'Hız'], true],
            ['Eklemek istediğiniz bir görüş var mı?', ServiceReviewQuestion::TYPE_TEXT, null, false],
        ])->map(fn (array $question, int $index): ServiceReviewQuestion => ServiceReviewQuestion::query()->create([
            'question' => $question[0],
            'type' => $question[1],
            'options' => $question[2],
            'is_required' => $question[3],
            'is_active' => true,
            'sort_order' => $index + 1,
        ]));

        foreach (range(0, 4) as $index) {
            $appointment = $appointments[$index];
            $completed = $index < 3;
            $review = ServiceReview::query()->create([
                'member_id' => $members[$index]->id,
                'provider_user_id' => $providers[$index % $providers->count()]->id,
                'reviewable_type' => Appointment::class,
                'reviewable_id' => $appointment->id,
                'service_type' => ServiceReview::SERVICE_APPOINTMENT,
                'service_title' => 'Analiz danışmanlığı görüşmesi',
                'service_reference' => 'RND-'.$appointment->id,
                'status' => $completed ? ServiceReview::STATUS_COMPLETED : ServiceReview::STATUS_PENDING,
                'overall_rating' => $completed ? [5, 4, 5][$index] : null,
                'public_comment' => $completed ? 'Süreç planlı, açıklayıcı ve beklentimize uygun ilerledi.' : null,
                'service_completed_at' => $appointment->end_at,
                'invited_at' => now()->subDays(6 - $index),
                'questions_locked_at' => now()->subDays(6 - $index),
                'submitted_at' => $completed ? now()->subDays(5 - $index) : null,
            ]);

            $questions->each(function (ServiceReviewQuestion $question) use ($review, $completed): void {
                $answer = null;
                if ($completed) {
                    $answer = ['value' => match ($question->type) {
                        ServiceReviewQuestion::TYPE_SCALE => 5,
                        ServiceReviewQuestion::TYPE_YES_NO => true,
                        ServiceReviewQuestion::TYPE_SINGLE_CHOICE => 'Analiz',
                        default => 'Sunum ve öneriler oldukça açıklayıcıydı.',
                    }];
                }

                ServiceReviewItem::query()->create([
                    'service_review_id' => $review->id,
                    'question_id' => $question->id,
                    'question_text' => $question->question,
                    'question_type' => $question->type,
                    'question_options' => $question->options,
                    'is_required' => $question->is_required,
                    'sort_order' => $question->sort_order,
                    'answer' => $answer,
                ]);
            });
        }

        $project = $projects->first();
        ServiceReview::query()->create([
            'member_id' => $project->member_id,
            'provider_user_id' => $providers->first()->id,
            'reviewable_type' => Project::class,
            'reviewable_id' => $project->id,
            'service_type' => ServiceReview::SERVICE_PROJECT,
            'service_title' => $project->title,
            'service_reference' => 'PRJ-'.$project->id,
            'status' => ServiceReview::STATUS_PENDING,
            'service_completed_at' => now()->subDay(),
            'invited_at' => now()->subDay(),
        ]);
    }

    private function createSystemActivity(User $actor, Collection $providers, int $blogCount): void
    {
        foreach ([
            [AdminNotification::TYPE_SYSTEM, AdminNotification::SEVERITY_SUCCESS, 'Örnek veriler hazır', $blogCount.' blog yazısı ve ilişkili kayıtlar üretildi.'],
            [AdminNotification::TYPE_INVENTORY, AdminNotification::SEVERITY_WARNING, 'Düşük stok uyarısı', 'Yönetici raporu şablonu kritik stok seviyesine yaklaştı.'],
            [AdminNotification::TYPE_APPOINTMENT, AdminNotification::SEVERITY_INFO, 'Yaklaşan randevular', 'Önümüzdeki hafta için yeni demo randevuları bulunuyor.'],
        ] as $index => $notification) {
            AdminNotification::query()->create([
                'user_id' => $actor->id,
                'type' => $notification[0],
                'severity' => $notification[1],
                'title' => $notification[2],
                'body' => $notification[3],
                'action_label' => 'Kontrol et',
                'action_url' => route('admin.dashboard'),
                'data' => ['demo' => true],
                'read_at' => $index === 2 ? now() : null,
            ]);
        }

        $providers->each(function (User $provider): void {
            AdminNotification::query()->create([
                'user_id' => $provider->id,
                'type' => AdminNotification::TYPE_MESSAGE,
                'severity' => AdminNotification::SEVERITY_INFO,
                'title' => 'Yeni örnek iletişim talepleri',
                'body' => 'Size yönlendirilen mesajları inceleyebilirsiniz.',
                'action_label' => 'Mesajları aç',
                'action_url' => route('admin.messages.index'),
                'data' => ['demo' => true],
            ]);
        });

        foreach (range(0, 9) as $index) {
            AuditLog::query()->create([
                'user_id' => $actor->id,
                'user_email' => $actor->email,
                'user_name' => $actor->name,
                'action' => $index % 4 === 0 ? 'update' : 'request',
                'route' => ['admin.blog.index', 'admin.projects.index', 'admin.products.index', 'admin.messages.index'][$index % 4],
                'method' => $index % 4 === 0 ? 'PUT' : 'GET',
                'status' => 200,
                'ip' => '127.0.0.1',
                'user_agent' => 'PROBABLUE Demo Data Factory',
                'uri' => '/admin/demo-activity/'.$index,
                'query' => ['demo' => true],
                'payload' => ['sample' => $index],
                'context' => ['source' => 'demo-data-factory'],
                'duration_ms' => 40 + ($index * 35),
            ])->forceFill([
                'created_at' => now()->subDays(60 - ($index * 6)),
                'updated_at' => now()->subDays(60 - ($index * 6)),
            ])->saveQuietly();
        }
    }

    private function createTrashScenarios(
        Collection $blogs,
        Collection $products,
        Collection $projects,
        Collection $galleries,
        Collection $media
    ): void {
        $blogs->last()?->delete();
        $products->last()?->delete();
        $projects->last()?->delete();
        $galleries->last()?->delete();
        $media->last()?->delete();
    }

    private function protectedUserIds(): Collection
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('roles') || ! Schema::hasTable('role_user')) {
            return collect();
        }

        return User::query()
            ->whereHas('roles', fn ($roles) => $roles->whereIn('slug', ['admin', 'superadmin']))
            ->pluck('id');
    }

    private function protectedMediaIds(Collection $protectedUserIds): Collection
    {
        $ids = collect();

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'avatar_media_id')) {
            $ids = $ids->merge(User::query()->whereKey($protectedUserIds)->pluck('avatar_media_id'));
        }

        if (Schema::hasTable('site_settings') && Schema::hasColumn('site_settings', 'admin_login_logo_media_id')) {
            $ids = $ids->merge(SiteSetting::query()->pluck('admin_login_logo_media_id'));
        }

        if (Schema::hasTable('site_homepage_configs')) {
            SiteHomepageConfig::query()->get(['settings'])->each(function (SiteHomepageConfig $config) use (&$ids): void {
                $settings = is_array($config->settings) ? $config->settings : [];
                $ids->push($settings['header_logo_media_id'] ?? null);
                $ids->push($settings['background_media_id'] ?? null);
            });
        }

        return $ids
            ->filter(fn ($id) => (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    private function filesScheduledForDeletion(Collection $protectedMediaIds): array
    {
        $files = [];

        if (Schema::hasTable('media')) {
            $query = Media::withTrashed();
            if ($protectedMediaIds->isNotEmpty()) {
                $query->whereNotIn('id', $protectedMediaIds);
            }

            $query->get(['disk', 'path', 'variants'])->each(function (Media $media) use (&$files): void {
                $paths = collect([$media->path])->merge(array_values($media->variants ?? []))->filter()->unique();
                foreach ($paths as $path) {
                    $files[] = ['disk' => $media->disk ?: 'public', 'path' => (string) $path];
                }
            });
        }

        if (Schema::hasTable('project_files')) {
            ProjectFile::withTrashed()->get(['disk', 'path'])->each(function (ProjectFile $file) use (&$files): void {
                $files[] = ['disk' => $file->disk ?: 'local', 'path' => $file->path];
            });
        }

        if (Schema::hasTable('members')) {
            Member::withTrashed()->whereNotNull('filepath')->get(['file_disk', 'filepath'])->each(function (Member $member) use (&$files): void {
                $files[] = ['disk' => $member->file_disk ?: 'local', 'path' => $member->filepath];
            });
        }

        return collect($files)
            ->filter(fn (array $file) => filled($file['path'] ?? null))
            ->unique(fn (array $file) => ($file['disk'] ?? '').':'.($file['path'] ?? ''))
            ->values()
            ->all();
    }

    private function deletePhysicalFiles(array $files): void
    {
        collect($files)
            ->groupBy('disk')
            ->each(function (Collection $diskFiles, string $disk): void {
                try {
                    Storage::disk($disk)->delete($diskFiles->pluck('path')->all());
                } catch (Throwable $exception) {
                    report($exception);
                }
            });
    }

    private function cleanAuthenticationState(Collection $protectedUserIds): void
    {
        if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
            DB::table('sessions')
                ->whereNotNull('user_id')
                ->whereNotIn('user_id', $protectedUserIds)
                ->delete();
        }

        if (Schema::hasTable('password_reset_tokens')) {
            $emails = User::query()->whereKey($protectedUserIds)->pluck('email');
            DB::table('password_reset_tokens')->whereNotIn('email', $emails)->delete();
        }
    }

    private function detachDeletedMenuEditor(Collection $protectedUserIds): void
    {
        if (! Schema::hasTable('admin_menu_settings') || ! Schema::hasColumn('admin_menu_settings', 'updated_by')) {
            return;
        }

        DB::table('admin_menu_settings')
            ->whereNotNull('updated_by')
            ->whereNotIn('updated_by', $protectedUserIds)
            ->update(['updated_by' => null]);
    }

    private function deleteExcept(string $table, string $column, Collection $preservedIds): int
    {
        $query = DB::table($table);

        if ($preservedIds->isNotEmpty()) {
            $query->whereNotIn($column, $preservedIds);
        }

        return $query->delete();
    }

    private function runLocked(Closure $callback): array
    {
        $lock = Cache::lock(self::LOCK_KEY, 180);
        if (! $lock->get()) {
            throw new RuntimeException('Başka bir veri fabrikası işlemi devam ediyor. Lütfen tamamlanmasını bekleyin.');
        }

        try {
            return $callback();
        } finally {
            $lock->release();
        }
    }

    private function module(string $key, string $label, string $icon, int $count): array
    {
        return compact('key', 'label', 'icon', 'count');
    }

    private function countTable(string $table): int
    {
        return Schema::hasTable($table) ? DB::table($table)->count() : 0;
    }

    private function nonAdminUserCount(): int
    {
        if (! Schema::hasTable('users')) {
            return 0;
        }

        return User::query()
            ->whereDoesntHave('roles', fn ($roles) => $roles->whereIn('slug', ['admin', 'superadmin']))
            ->count();
    }

    private function siteContentCount(): int
    {
        return collect([
            'site_pages', 'site_faqs', 'site_counters', 'site_navigation_items',
            'home_sliders', 'site_homepage_sections',
        ])->sum(fn (string $table): int => $this->countTable($table));
    }

    private function spreadCreatedAt(Model $model, int $daysAgo): void
    {
        $timestamp = now()->subDays(max(0, $daysAgo));
        $model->forceFill(['created_at' => $timestamp, 'updated_at' => $timestamp])->saveQuietly();
    }

    private function demoSvg(string $from, string $to, int $number): string
    {
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1280" height="800" viewBox="0 0 1280 800">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop stop-color="{$from}"/><stop offset="1" stop-color="{$to}"/></linearGradient>
    <pattern id="p" width="48" height="48" patternUnits="userSpaceOnUse"><path d="M48 0H0V48" fill="none" stroke="#fff" stroke-opacity=".12"/></pattern>
  </defs>
  <rect width="1280" height="800" rx="36" fill="url(#g)"/>
  <rect width="1280" height="800" rx="36" fill="url(#p)"/>
  <circle cx="1050" cy="160" r="230" fill="#fff" fill-opacity=".12"/>
  <circle cx="180" cy="700" r="280" fill="#000" fill-opacity=".1"/>
  <g fill="#fff"><text x="92" y="144" font-family="sans-serif" font-size="34" font-weight="700">PROBABLUE</text><text x="92" y="194" font-family="sans-serif" font-size="22" opacity=".78">İstatistiksel Analiz ve Danışmanlık</text><text x="92" y="620" font-family="sans-serif" font-size="96" font-weight="700" opacity=".92">0{$number}</text><text x="92" y="676" font-family="sans-serif" font-size="24" opacity=".78">Örnek içerik görseli</text></g>
</svg>
SVG;
    }
}
