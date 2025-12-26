<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_email')->nullable();
            $table->string('user_name')->nullable();

            $table->string('action', 32)->index(); // request
            $table->string('route')->nullable()->index();
            $table->string('method', 10)->nullable();
            $table->unsignedSmallInteger('status')->nullable()->index();

            $table->string('ip', 64)->nullable()->index();
            $table->text('user_agent')->nullable();

            $table->string('uri')->nullable();
            $table->text('query')->nullable();
            $table->longText('payload')->nullable();
            $table->longText('context')->nullable();

            $table->unsignedInteger('duration_ms')->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
