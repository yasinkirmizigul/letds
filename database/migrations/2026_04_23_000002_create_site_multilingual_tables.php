<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('site_languages')) {
            Schema::create('site_languages', function (Blueprint $table) {
                $table->id();
                $table->string('code', 10)->unique();
                $table->string('name', 120);
                $table->string('native_name', 120);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->boolean('is_rtl')->default(false);
                $table->unsignedInteger('sort_order')->default(1);
                $table->timestamps();

                $table->index(['is_active', 'sort_order']);
            });
        }

        if (!Schema::hasTable('site_page_translations')) {
            Schema::create('site_page_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_page_id')->constrained('site_pages')->cascadeOnDelete();
                $table->string('locale', 10);
                $table->string('title')->nullable();
                $table->string('slug')->nullable();
                $table->string('hero_kicker')->nullable();
                $table->text('excerpt')->nullable();
                $table->longText('content')->nullable();
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->text('meta_keywords')->nullable();
                $table->timestamps();

                $table->unique(['site_page_id', 'locale']);
                $table->unique('slug');
            });
        }

        if (!Schema::hasTable('site_faq_translations')) {
            Schema::create('site_faq_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_faq_id')->constrained('site_faqs')->cascadeOnDelete();
                $table->string('locale', 10);
                $table->string('group_label')->nullable();
                $table->string('question')->nullable();
                $table->text('answer')->nullable();
                $table->timestamps();

                $table->unique(['site_faq_id', 'locale']);
            });
        }

        if (!Schema::hasTable('site_counter_translations')) {
            Schema::create('site_counter_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_counter_id')->constrained('site_counters')->cascadeOnDelete();
                $table->string('locale', 10);
                $table->string('label')->nullable();
                $table->string('prefix', 30)->nullable();
                $table->string('suffix', 30)->nullable();
                $table->string('description', 500)->nullable();
                $table->timestamps();

                $table->unique(['site_counter_id', 'locale']);
            });
        }

        if (!Schema::hasTable('site_navigation_item_translations')) {
            Schema::create('site_navigation_item_translations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('site_navigation_item_id');
                $table->string('locale', 10);
                $table->string('title')->nullable();
                $table->timestamps();

                $table->foreign('site_navigation_item_id', 'site_nav_item_trans_fk')
                    ->references('id')
                    ->on('site_navigation_items')
                    ->cascadeOnDelete();

                $table->unique(['site_navigation_item_id', 'locale'], 'site_nav_item_trans_loc_uq');
            });
        }

        if (!Schema::hasTable('site_setting_translations')) {
            Schema::create('site_setting_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_setting_id')->constrained('site_settings')->cascadeOnDelete();
                $table->string('locale', 10);
                $table->string('site_name')->nullable();
                $table->string('site_tagline')->nullable();
                $table->string('hero_notice', 500)->nullable();
                $table->text('address_line')->nullable();
                $table->string('map_title')->nullable();
                $table->text('office_hours')->nullable();
                $table->text('footer_note')->nullable();
                $table->string('under_construction_title')->nullable();
                $table->text('under_construction_message')->nullable();
                $table->json('ui_lines')->nullable();
                $table->timestamps();

                $table->unique(['site_setting_id', 'locale']);
            });
        }

        if (!Schema::hasTable('home_slider_translations')) {
            Schema::create('home_slider_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('home_slider_id')->constrained('home_sliders')->cascadeOnDelete();
                $table->string('locale', 10);
                $table->string('badge')->nullable();
                $table->string('title')->nullable();
                $table->string('subtitle', 500)->nullable();
                $table->text('body')->nullable();
                $table->string('cta_label')->nullable();
                $table->string('cta_url', 500)->nullable();
                $table->timestamps();

                $table->unique(['home_slider_id', 'locale']);
            });
        }

        if (Schema::hasTable('site_languages') && !DB::table('site_languages')->where('code', 'tr')->exists()) {
            DB::table('site_languages')->insert([
                'code' => 'tr',
                'name' => 'Turkish',
                'native_name' => 'Türkçe',
                'is_active' => true,
                'is_default' => true,
                'is_rtl' => false,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('home_slider_translations');
        Schema::dropIfExists('site_setting_translations');
        Schema::dropIfExists('site_navigation_item_translations');
        Schema::dropIfExists('site_counter_translations');
        Schema::dropIfExists('site_faq_translations');
        Schema::dropIfExists('site_page_translations');

        Schema::dropIfExists('site_languages');
    }
};
