<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('title', 190);
            $table->string('sku', 120)->nullable()->unique();
            $table->string('barcode', 120)->nullable()->unique();
            $table->json('option_values')->nullable();
            $table->decimal('price', 14, 2)->nullable();
            $table->decimal('sale_price', 14, 2)->nullable();
            $table->string('currency', 3)->default('TRY');
            $table->decimal('stock', 12, 3)->nullable();
            $table->decimal('low_stock_threshold', 12, 3)->default(5);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(1)->index();
            $table->timestamps();

            $table->index(['product_id', 'is_active']);
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('ecommerce_orders')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40)->index();
            $table->string('reason', 120)->nullable();
            $table->decimal('quantity', 12, 3);
            $table->decimal('before_stock', 12, 3)->nullable();
            $table->decimal('after_stock', 12, 3)->nullable();
            $table->string('reference', 160)->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['product_id', 'occurred_at']);
            $table->index(['product_variant_id', 'occurred_at']);
        });

        Schema::create('ecommerce_coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name', 190);
            $table->string('type', 40)->default('fixed')->index();
            $table->decimal('value', 14, 2)->default(0);
            $table->decimal('min_order_total', 14, 2)->nullable();
            $table->decimal('max_discount_total', 14, 2)->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->unsignedInteger('per_customer_limit')->nullable();
            $table->json('applies_to')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('ecommerce_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained('ecommerce_orders')->nullOnDelete();
            $table->string('invoice_number', 80)->unique();
            $table->string('type', 40)->default('invoice')->index();
            $table->string('status', 40)->default('draft')->index();
            $table->string('currency', 3)->default('TRY');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('grand_total', 14, 2)->default(0);
            $table->json('billing_snapshot')->nullable();
            $table->json('line_snapshot')->nullable();
            $table->string('pdf_path', 500)->nullable();
            $table->timestamp('issued_at')->nullable()->index();
            $table->timestamp('due_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_integration_id')->nullable()->constrained('payment_integrations')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('ecommerce_orders')->nullOnDelete();
            $table->string('provider', 80)->index();
            $table->string('event_type', 120)->nullable()->index();
            $table->string('event_id', 190)->nullable()->index();
            $table->string('status', 40)->default('received')->index();
            $table->json('headers')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('received_at')->index();
            $table->timestamp('processed_at')->nullable()->index();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'event_id'], 'payment_webhook_provider_event_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
        Schema::dropIfExists('ecommerce_invoices');
        Schema::dropIfExists('ecommerce_coupons');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('product_variants');
    }
};
