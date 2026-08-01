<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_homepage_configs', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120)->unique();
            $table->json('content')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('site_homepage_config_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_homepage_config_id');
            $table->string('locale', 10);
            $table->json('content')->nullable();
            $table->timestamps();

            $table->foreign('site_homepage_config_id', 'site_homepage_translations_config_fk')
                ->references('id')
                ->on('site_homepage_configs')
                ->cascadeOnDelete();

            $table->unique(
                ['site_homepage_config_id', 'locale'],
                'site_homepage_config_translations_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_homepage_config_translations');
        Schema::dropIfExists('site_homepage_configs');
    }
};
