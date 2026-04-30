<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('seo_base_url')->nullable()->after('ui_lines');
            $table->boolean('sitemap_include_home')->default(true)->after('seo_base_url');
            $table->boolean('sitemap_include_pages')->default(true)->after('sitemap_include_home');
            $table->boolean('sitemap_include_contact')->default(true)->after('sitemap_include_pages');
            $table->boolean('sitemap_include_member_pages')->default(true)->after('sitemap_include_contact');
            $table->text('sitemap_extra_urls')->nullable()->after('sitemap_include_member_pages');
            $table->longText('robots_txt_content')->nullable()->after('sitemap_extra_urls');
            $table->longText('llms_txt_content')->nullable()->after('robots_txt_content');
            $table->timestamp('seo_files_generated_at')->nullable()->after('llms_txt_content');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'seo_base_url',
                'sitemap_include_home',
                'sitemap_include_pages',
                'sitemap_include_contact',
                'sitemap_include_member_pages',
                'sitemap_extra_urls',
                'robots_txt_content',
                'llms_txt_content',
                'seo_files_generated_at',
            ]);
        });
    }
};
