<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 60);
            $table->string('title', 120);
            $table->string('integration_type', 60)->default('payment_gateway');
            $table->string('environment', 20)->default('sandbox');
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(1);
            $table->json('supported_currencies')->nullable();
            $table->json('allowed_payment_methods')->nullable();
            $table->boolean('installment_enabled')->default(false);
            $table->json('installment_options')->nullable();
            $table->string('success_url', 500)->nullable();
            $table->string('cancel_url', 500)->nullable();
            $table->text('webhook_ip_whitelist')->nullable();
            $table->text('notes')->nullable();
            $table->longText('credentials')->nullable();
            $table->timestamp('credentials_rotated_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'environment']);
            $table->index(['provider', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_integrations');
    }
};
