<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->applyOrder([
            'site.home',
            'about',
            'site.services.index',
            'site.blog.index',
            'site.galleries.index',
            'site.faqs.index',
            'site.contact-messages.create',
        ]);
    }

    public function down(): void
    {
        $this->applyOrder([
            'site.home',
            'site.blog.index',
            'site.galleries.index',
            'site.contact-messages.create',
            'about',
            'site.faqs.index',
            'site.services.index',
        ]);
    }

    /**
     * @param  array<int, string>  $order
     */
    private function applyOrder(array $order): void
    {
        if (! Schema::hasTable('site_navigation_items')) {
            return;
        }

        $primaryItems = DB::table('site_navigation_items')
            ->where('location', 'primary')
            ->whereNull('parent_id');

        $orderedIds = collect($order)
            ->map(fn (string $key): ?int => $key === 'about'
                ? $this->aboutNavigationId()
                : $this->routeNavigationId($key))
            ->filter()
            ->unique()
            ->values();

        $remainingIds = (clone $primaryItems)
            ->when($orderedIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $orderedIds->all()))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id');

        DB::transaction(function () use ($orderedIds, $remainingIds): void {
            $orderedIds
                ->concat($remainingIds)
                ->values()
                ->each(fn ($id, int $index) => DB::table('site_navigation_items')
                    ->where('id', $id)
                    ->update(['sort_order' => $index + 1]));
        });
    }

    private function routeNavigationId(string $routeName): ?int
    {
        return DB::table('site_navigation_items')
            ->where('location', 'primary')
            ->whereNull('parent_id')
            ->where('route_name', $routeName)
            ->value('id');
    }

    private function aboutNavigationId(): ?int
    {
        $aboutPageId = Schema::hasTable('site_pages')
            ? DB::table('site_pages')->where('slug', 'hakkimizda')->value('id')
            : null;

        return DB::table('site_navigation_items')
            ->where('location', 'primary')
            ->whereNull('parent_id')
            ->where(function ($query) use ($aboutPageId): void {
                if ($aboutPageId) {
                    $query->where('site_page_id', $aboutPageId)
                        ->orWhere('title', 'Hakkımızda');

                    return;
                }

                $query->where('title', 'Hakkımızda');
            })
            ->value('id');
    }
};
