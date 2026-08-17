<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_dashboard_preferences', function (Blueprint $table) {
            $table->json('layout_rows')->nullable()->after('section_order');
        });
    }

    public function down(): void
    {
        Schema::table('admin_dashboard_preferences', function (Blueprint $table) {
            $table->dropColumn('layout_rows');
        });
    }
};
