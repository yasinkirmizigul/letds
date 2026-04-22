<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_navigation_items', function (Blueprint $table) {
            $table->id();
            $table->string('location')->default('primary');
            $table->foreignId('parent_id')->nullable()->constrained('site_navigation_items')->nullOnDelete();
            $table->foreignId('site_page_id')->nullable()->constrained('site_pages')->nullOnDelete();
            $table->string('title');
            $table->string('icon_class')->nullable();
            $table->string('link_type')->default('page');
            $table->string('url')->nullable();
            $table->string('target')->default('_self');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_navigation_items');
    }
};
