<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_analysis_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('project_file_id')->nullable()->constrained('project_files')->nullOnDelete();
            $table->text('message');
            $table->string('status', 20)->default('pending');
            $table->timestamps();
            $table->index(['project_id', 'status']);
        });

        if (Schema::hasTable('site_navigation_items')) {
            DB::table('site_navigation_items')->where('title', 'Hizmetler')->update(['title' => 'Neler Sunuyoruz?', 'updated_at' => now()]);
        }

        if (Schema::hasTable('site_homepage_sections')) {
            DB::table('site_homepage_sections')->where('eyebrow', '01 — Hizmetler')->update(['eyebrow' => '01 — Neler Sunuyoruz?', 'updated_at' => now()]);
            DB::table('site_homepage_sections')->where('title', 'Hizmetlerimiz')->update(['title' => 'Neler Sunuyoruz?', 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_analysis_requests');
    }
};
