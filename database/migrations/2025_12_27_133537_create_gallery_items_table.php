<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gallery_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('gallery_id')->constrained('galleries')->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->restrictOnDelete();

            $table->unsignedInteger('sort_order')->default(0);

            $table->string('caption', 255)->nullable();
            $table->string('alt', 255)->nullable();
            $table->string('link_url', 2048)->nullable();
            $table->string('link_target', 20)->nullable(); // _blank / _self

            $table->timestamps();

            $table->index(['gallery_id', 'sort_order']);
            $table->unique(['gallery_id', 'media_id']); // aynı medya aynı galeriye iki kere eklenmesin
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gallery_items');
    }
};
