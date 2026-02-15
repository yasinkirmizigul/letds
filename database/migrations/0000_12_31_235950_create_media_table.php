<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('disk', 50)->default('public');
            $table->string('path'); // media/YYYY/MM/uuid.ext
            $table->json('variants')->nullable();

            $table->string('original_name')->nullable();
            $table->string('mime_type', 120)->index();
            $table->unsignedBigInteger('size')->default(0);

            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();

            $table->string('title')->nullable();
            $table->string('alt')->nullable();

            $table->json('meta')->nullable();

            $table->softDeletes();
            $table->index('deleted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
