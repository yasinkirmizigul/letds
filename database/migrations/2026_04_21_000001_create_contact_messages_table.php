<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('recipient_user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('member_id')
                ->nullable()
                ->constrained('members')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('recipient_name');
            $table->string('sender_type', 20);
            $table->string('sender_name');
            $table->string('sender_surname');
            $table->string('sender_email')->nullable();
            $table->string('sender_phone', 50)->nullable();
            $table->json('preferred_channels')->nullable();
            $table->string('subject', 190);
            $table->string('priority', 20)->default('normal');
            $table->text('message');
            $table->dateTime('read_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['recipient_user_id', 'created_at']);
            $table->index(['priority', 'created_at']);
            $table->index(['sender_type', 'created_at']);
            $table->index(['read_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
