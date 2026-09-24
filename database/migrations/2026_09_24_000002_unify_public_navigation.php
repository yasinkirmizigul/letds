<?php

use App\Support\Site\SiteNavigationRoutes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_navigation_items')) {
            return;
        }

        DB::transaction(function (): void {
            DB::table('site_navigation_items')
                ->where('location', 'primary')
                ->update(['is_active' => false, 'updated_at' => now()]);

            DB::table('site_navigation_items')
                ->whereIn('route_name', [SiteNavigationRoutes::BLOG, SiteNavigationRoutes::GALLERIES])
                ->update(['is_active' => false, 'updated_at' => now()]);

            $this->upsertRouteItem('Neler Sunuyoruz?', SiteNavigationRoutes::SERVICES_OFFER, 1, 'ki-outline ki-briefcase');
            $this->upsertRouteItem('Nasıl İlerliyoruz?', SiteNavigationRoutes::SERVICES_PROCESS, 2, 'ki-outline ki-route');
            $this->upsertRouteItem('Birlikte Başlayalım', SiteNavigationRoutes::SERVICES_START, 3, 'ki-outline ki-rocket');
            $this->activateAboutItem(4);
            $this->upsertRouteItem('SSS', SiteNavigationRoutes::FAQS, 5, 'ki-outline ki-message-question');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_navigation_items')) {
            return;
        }

        DB::table('site_navigation_items')
            ->where('location', 'primary')
            ->whereIn('route_name', [
                SiteNavigationRoutes::SERVICES_OFFER,
                SiteNavigationRoutes::SERVICES_PROCESS,
                SiteNavigationRoutes::SERVICES_START,
            ])
            ->delete();
    }

    private function upsertRouteItem(string $title, string $routeName, int $sortOrder, string $iconClass): void
    {
        $item = DB::table('site_navigation_items')
            ->where('location', 'primary')
            ->whereNull('parent_id')
            ->where('route_name', $routeName)
            ->first();

        $values = [
            'parent_id' => null,
            'site_page_id' => null,
            'title' => $title,
            'icon_class' => $iconClass,
            'link_type' => 'route',
            'url' => null,
            'route_name' => $routeName,
            'target' => '_self',
            'is_active' => true,
            'sort_order' => $sortOrder,
            'updated_at' => now(),
        ];

        if ($item) {
            DB::table('site_navigation_items')->where('id', $item->id)->update($values);

            return;
        }

        DB::table('site_navigation_items')->insert(['location' => 'primary', ...$values, 'created_at' => now()]);
    }

    private function activateAboutItem(int $sortOrder): void
    {
        $aboutPageId = Schema::hasTable('site_pages')
            ? DB::table('site_pages')->where('slug', 'hakkimizda')->value('id')
            : null;

        $query = DB::table('site_navigation_items')
            ->where('location', 'primary')
            ->whereNull('parent_id');

        $aboutItem = $query
            ->where(function ($builder) use ($aboutPageId): void {
                if ($aboutPageId) {
                    $builder->where('site_page_id', $aboutPageId)->orWhere('title', 'Hakkımızda');
                } else {
                    $builder->where('title', 'Hakkımızda');
                }
            })
            ->first();

        if (! $aboutItem) {
            return;
        }

        DB::table('site_navigation_items')->where('id', $aboutItem->id)->update([
            'title' => 'Hakkımızda',
            'is_active' => true,
            'sort_order' => $sortOrder,
            'updated_at' => now(),
        ]);
    }
};
