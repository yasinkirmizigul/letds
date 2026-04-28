<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('surname');
            $table->string('filepath')->nullable();
            $table->string('file_disk', 40)->nullable();
            $table->string('file_original_name')->nullable();
            $table->string('file_mime_type', 190)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('membership_terms_accepted_at')->nullable();
            $table->string('membership_terms_version', 50)->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('membership_ended_at')->nullable();
            $table->string('suspension_reason')->nullable();
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['is_active', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
