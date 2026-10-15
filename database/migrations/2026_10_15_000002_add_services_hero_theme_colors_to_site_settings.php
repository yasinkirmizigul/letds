<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->json('services_symbol_light_colors')->nullable()->after('services_symbol_colors');
            $table->string('services_hero_light_background', 7)->nullable()->after('services_symbol_light_colors');
            $table->string('services_hero_dark_background', 7)->nullable()->after('services_hero_light_background');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'services_symbol_light_colors',
                'services_hero_light_background',
                'services_hero_dark_background',
            ]);
        });
    }
};
