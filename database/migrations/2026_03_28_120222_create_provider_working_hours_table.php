<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('provider_working_hours', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provider_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('day_of_week'); // 0=Pazar ... 6=Cumartesi
            $table->boolean('is_enabled')->default(false);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->timestamps();

            $table->unique(['provider_id', 'day_of_week'], 'uq_provider_day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_working_hours');
    }
};
