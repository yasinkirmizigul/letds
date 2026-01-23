<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'status')) {
                $table->string('status', 50)->default('appointment_pending')->index();
            }
            if (!Schema::hasColumn('projects', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->index();
            }
            if (!Schema::hasColumn('projects', 'featured_at')) {
                $table->timestamp('featured_at')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'featured_at')) $table->dropColumn('featured_at');
            if (Schema::hasColumn('projects', 'is_featured')) $table->dropColumn('is_featured');
            if (Schema::hasColumn('projects', 'status')) $table->dropColumn('status');
        });
    }
};
