<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_page_id')->nullable()->constrained('site_pages')->nullOnDelete();
            $table->string('group_label')->nullable();
            $table->string('question');
            $table->longText('answer');
            $table->string('icon_class')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_faqs');
    }
};
