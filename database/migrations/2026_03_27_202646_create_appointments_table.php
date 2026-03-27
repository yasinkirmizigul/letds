<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provider_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('member_id')
                ->constrained('members')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->unsignedTinyInteger('blocks')->default(1);

            $table->string('status', 50)->default('booked');

            $table->text('notes_internal')->nullable();

            $table->dateTime('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();

            $table->foreignId('cancelled_by_user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('appointments')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['provider_id', 'start_at']);
            $table->index(['member_id', 'start_at']);
            $table->index(['status', 'start_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
