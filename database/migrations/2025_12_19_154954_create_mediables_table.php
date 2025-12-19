<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mediables', function (Blueprint $table) {
            $table->id();

            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();

            $table->morphs('mediable'); // mediable_type + mediable_id
            $table->string('collection', 50)->default('default'); // featured, gallery, editor
            $table->unsignedInteger('order')->default(0);

            $table->timestamps();

            $table->unique(['media_id', 'mediable_type', 'mediable_id', 'collection'], 'mediables_unique');
            $table->index(['mediable_type', 'mediable_id', 'collection'], 'mediables_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mediables');
    }
};
