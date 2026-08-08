<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_homepage_sections', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40)->default('features');
            $table->string('eyebrow', 80)->nullable();
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('site_homepage_section_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_homepage_section_id');
            $table->string('locale', 10);
            $table->string('eyebrow', 80)->nullable();
            $table->string('title', 160)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['site_homepage_section_id', 'locale'], 'homepage_section_translation_unique');
            $table->foreign('site_homepage_section_id', 'homepage_section_translation_section_fk')
                ->references('id')
                ->on('site_homepage_sections')
                ->cascadeOnDelete();
        });

        Schema::create('site_homepage_section_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_homepage_section_id');
            $table->string('title', 160);
            $table->text('description');
            $table->string('icon', 40)->default('sparkles');
            $table->string('link_label', 80)->nullable();
            $table->string('link_url', 500)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();

            $table->foreign('site_homepage_section_id', 'homepage_section_item_section_fk')
                ->references('id')
                ->on('site_homepage_sections')
                ->cascadeOnDelete();
        });

        Schema::create('site_homepage_section_item_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_homepage_section_item_id');
            $table->string('locale', 10);
            $table->string('title', 160)->nullable();
            $table->text('description')->nullable();
            $table->string('link_label', 80)->nullable();
            $table->timestamps();

            $table->unique(['site_homepage_section_item_id', 'locale'], 'homepage_section_item_translation_unique');
            $table->foreign('site_homepage_section_item_id', 'homepage_section_item_translation_item_fk')
                ->references('id')
                ->on('site_homepage_section_items')
                ->cascadeOnDelete();
        });

        $now = now();
        $sectionId = DB::table('site_homepage_sections')->insertGetId([
            'type' => 'features',
            'eyebrow' => 'Neden Biz?',
            'title' => 'İşinizi birlikte ileri taşıyalım.',
            'description' => 'Her projede anlaşılır iletişimi, ölçülebilir kaliteyi ve ihtiyaçlarınıza uyum sağlayan bir çalışma biçimini merkeze alıyoruz.',
            'settings' => json_encode([
                'columns' => 3,
                'alignment' => 'left',
                'surface' => 'tint',
                'accent_color' => '#ec6367',
            ], JSON_THROW_ON_ERROR),
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('site_homepage_section_items')->insert([
            [
                'site_homepage_section_id' => $sectionId,
                'title' => 'Müşteri Memnuniyeti',
                'description' => 'İhtiyacınızı doğru anlayıp her aşamada açık iletişim kurarak kalıcı değer üretiriz.',
                'icon' => 'heart',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'site_homepage_section_id' => $sectionId,
                'title' => 'Esnek Çalışma',
                'description' => 'Süreci hedeflerinize, takviminize ve değişen önceliklerinize göre birlikte şekillendiririz.',
                'icon' => 'adjustments',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'site_homepage_section_id' => $sectionId,
                'title' => 'Şeffaf Süreç',
                'description' => 'Kararları veriye dayandırır, ilerlemeyi görünür kılar ve sizi her adımda haberdar ederiz.',
                'icon' => 'chart',
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_homepage_section_item_translations');
        Schema::dropIfExists('site_homepage_section_items');
        Schema::dropIfExists('site_homepage_section_translations');
        Schema::dropIfExists('site_homepage_sections');
    }
};
