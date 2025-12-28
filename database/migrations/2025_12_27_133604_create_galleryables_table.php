<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('galleryables', function (Blueprint $table) {
            $table->id();

            $table->foreignId('gallery_id')->constrained('galleries')->cascadeOnDelete();

            // morph target
            $table->string('galleryable_type', 191);
            $table->unsignedBigInteger('galleryable_id');

            $table->string('slot', 30)->default('main'); // main/sidebar
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['galleryable_type', 'galleryable_id']);
            $table->index(['gallery_id']);
            $table->index(['slot', 'sort_order']);

            // aynı içerikte aynı galeri aynı slota bir kez
            $table->unique(
                ['gallery_id', 'galleryable_type', 'galleryable_id', 'slot'],
                'uniq_galleryable_attach'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('galleryables');
    }
};
