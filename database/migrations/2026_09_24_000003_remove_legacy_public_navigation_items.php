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

        DB::table('site_navigation_items')
            ->where(function ($query): void {
                $query->whereIn('route_name', [
                    SiteNavigationRoutes::BLOG,
                    SiteNavigationRoutes::GALLERIES,
                ])->orWhere(function ($primaryQuery): void {
                    $primaryQuery
                        ->where('location', 'primary')
                        ->whereIn('route_name', [
                            SiteNavigationRoutes::HOME,
                            SiteNavigationRoutes::SERVICES,
                            SiteNavigationRoutes::CONTACT,
                        ]);
                });
            })
            ->delete();
    }

    public function down(): void
    {
        // Legacy navigation is intentionally not recreated. The canonical public
        // navigation remains managed by the preceding migration.
    }
};
