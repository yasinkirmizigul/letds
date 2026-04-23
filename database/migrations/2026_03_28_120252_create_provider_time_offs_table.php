<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('provider_time_offs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provider_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->string('reason')->nullable();
            $table->string('block_type', 50)->default('manual');

            $table->timestamps();

            $table->index(['provider_id', 'start_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_time_offs');
    }
};
