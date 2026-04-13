<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('provider_time_offs', function (Blueprint $table) {
            $table->string('block_type', 50)->default('manual')->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('provider_time_offs', function (Blueprint $table) {
            $table->dropColumn('block_type');
        });
    }
};
