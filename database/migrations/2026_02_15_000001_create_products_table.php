<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // Core
            $table->string('title');
            $table->string('slug')->unique();

            // Content
            $table->longText('content')->nullable();

            // ✅ Product optional fields
            $table->string('sku', 100)->nullable()->unique();
            $table->decimal('price', 12, 2)->nullable();
            $table->unsignedInteger('stock')->nullable();

            
            // ✅ Product extended optional fields
            $table->string('barcode', 100)->nullable()->unique();
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->string('currency', 3)->nullable(); // e.g. TRY, USD
            $table->unsignedTinyInteger('vat_rate')->nullable(); // percent 0-100

            $table->string('brand', 120)->nullable();
            $table->decimal('weight', 10, 3)->nullable();
            $table->decimal('width', 10, 3)->nullable();
            $table->decimal('height', 10, 3)->nullable();
            $table->decimal('length', 10, 3)->nullable();

            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
// SEO
            $table->string('meta_title', 255)->nullable();
            $table->string('meta_description', 255)->nullable();
            $table->string('meta_keywords', 500)->nullable();

            // Status
            $table->string('status', 20)->default('draft')->index(); // draft|active|archived|appointment_pending
            $table->boolean('is_featured')->default(false)->index();
            $table->timestamp('featured_at')->nullable()->index();

            // Optional appointment link (FK yok: Appointment modülü yoksa kırmayalım)
            $table->unsignedBigInteger('appointment_id')->nullable()->index();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
