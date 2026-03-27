<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appointment_slots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('appointment_id')
                ->constrained('appointments')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('provider_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->dateTime('slot_start_at');
            $table->timestamps();

            $table->unique(['provider_id', 'slot_start_at'], 'uq_provider_slot_start');
            $table->index(['appointment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_slots');
    }
};
