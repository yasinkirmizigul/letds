<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_navigation_items', function (Blueprint $table) {
            $table->string('route_name', 120)->nullable()->after('url');
        });

        if (DB::table('site_navigation_items')->exists()) {
            return;
        }

        $now = now();
        $primaryItems = [
            ['title' => 'Ana Sayfa', 'route_name' => 'site.home', 'icon_class' => 'ki-outline ki-home'],
            ['title' => 'Blog', 'route_name' => 'site.blog.index', 'icon_class' => 'ki-outline ki-notepad'],
            ['title' => 'Galeri', 'route_name' => 'site.galleries.index', 'icon_class' => 'ki-outline ki-picture'],
            ['title' => 'İletişim', 'route_name' => 'site.contact-messages.create', 'icon_class' => 'ki-outline ki-messages'],
        ];
        $footerItems = [
            ['title' => 'Blog', 'route_name' => 'site.blog.index', 'icon_class' => 'ki-outline ki-notepad'],
            ['title' => 'Galeri', 'route_name' => 'site.galleries.index', 'icon_class' => 'ki-outline ki-picture'],
            ['title' => 'İletişim', 'route_name' => 'site.contact-messages.create', 'icon_class' => 'ki-outline ki-messages'],
        ];

        foreach (['primary' => $primaryItems, 'footer' => $footerItems] as $location => $items) {
            foreach ($items as $index => $item) {
                DB::table('site_navigation_items')->insert([
                    'location' => $location,
                    'parent_id' => null,
                    'site_page_id' => null,
                    'title' => $item['title'],
                    'icon_class' => $item['icon_class'],
                    'link_type' => 'route',
                    'url' => null,
                    'route_name' => $item['route_name'],
                    'target' => '_self',
                    'is_active' => true,
                    'sort_order' => $index + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('site_navigation_items')->where('link_type', 'route')->delete();

        Schema::table('site_navigation_items', function (Blueprint $table) {
            $table->dropColumn('route_name');
        });
    }
};
