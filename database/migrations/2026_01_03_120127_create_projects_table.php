<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            // Core
            $table->string('title');
            $table->string('slug')->unique();

            // Blog gibi content
            $table->longText('content')->nullable();

            // SEO
            $table->string('meta_title', 255)->nullable();
            $table->string('meta_description', 255)->nullable();
            $table->string('meta_keywords', 500)->nullable();

            // Status
            $table->string('status', 20)->default('draft')->index(); // draft|active|archived
            $table->boolean('is_featured')->default(false)->index();
            $table->timestamp('featured_at')->nullable()->index();

            // Optional appointment link (FK yok: Appointment modülü yoksa kırmayalım)
            $table->unsignedBigInteger('appointment_id')->nullable()->index();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
