<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 80)->default('system')->index();
            $table->string('severity', 20)->default('info')->index();
            $table->string('title', 190);
            $table->text('body')->nullable();
            $table->string('action_label', 80)->nullable();
            $table->string('action_url', 600)->nullable();
            $table->string('source_type', 120)->nullable()->index();
            $table->unsignedBigInteger('source_id')->nullable()->index();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamp('dismissed_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'read_at', 'created_at'], 'admin_notifications_user_read_created_idx');
            $table->index(['source_type', 'source_id'], 'admin_notifications_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};
