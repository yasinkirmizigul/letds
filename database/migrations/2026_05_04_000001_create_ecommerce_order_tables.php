<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 40)->unique();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->foreignId('payment_integration_id')->nullable()->constrained('payment_integrations')->nullOnDelete();

            $table->string('channel', 40)->default('admin')->index();
            $table->string('reference_code', 120)->nullable()->index();
            $table->string('status', 40)->default('draft')->index();
            $table->string('payment_status', 40)->default('unpaid')->index();
            $table->string('fulfillment_status', 40)->default('unfulfilled')->index();

            $table->string('customer_name', 190);
            $table->string('customer_email', 190)->nullable()->index();
            $table->string('customer_phone', 60)->nullable();
            $table->string('customer_company', 190)->nullable();
            $table->string('customer_tax_number', 80)->nullable();
            $table->string('customer_tax_office', 120)->nullable();

            $table->string('currency', 3)->default('TRY');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('order_discount_total', 14, 2)->default(0);
            $table->decimal('discount_total', 14, 2)->default(0);
            $table->decimal('shipping_total', 14, 2)->default(0);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('grand_total', 14, 2)->default(0);
            $table->decimal('paid_total', 14, 2)->default(0);
            $table->decimal('refunded_total', 14, 2)->default(0);

            $table->string('payment_method', 80)->nullable();
            $table->string('shipping_carrier', 120)->nullable();
            $table->string('tracking_number', 120)->nullable()->index();
            $table->string('tracking_url', 500)->nullable();

            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();
            $table->text('customer_note')->nullable();
            $table->text('internal_note')->nullable();
            $table->json('custom_fields')->nullable();

            $table->timestamp('ordered_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'payment_status', 'fulfillment_status'], 'ecommerce_orders_state_idx');
            $table->index(['created_at', 'grand_total'], 'ecommerce_orders_created_total_idx');
        });

        Schema::create('ecommerce_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('ecommerce_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_title', 255);
            $table->string('sku', 120)->nullable()->index();
            $table->string('barcode', 120)->nullable();
            $table->string('brand', 160)->nullable();
            $table->decimal('quantity', 12, 3)->default(1);
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount_total', 14, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->string('currency', 3)->default('TRY');
            $table->string('fulfillment_status', 40)->default('unfulfilled')->index();
            $table->json('custom_fields')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'product_id']);
        });

        Schema::create('ecommerce_order_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('ecommerce_orders')->cascadeOnDelete();
            $table->foreignId('payment_integration_id')->nullable()->constrained('payment_integrations')->nullOnDelete();
            $table->string('type', 40)->default('sale')->index();
            $table->string('status', 40)->default('pending')->index();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('currency', 3)->default('TRY');
            $table->string('gateway_transaction_id', 190)->nullable()->index();
            $table->string('gateway_reference', 190)->nullable()->index();
            $table->timestamp('processed_at')->nullable()->index();
            $table->json('payload')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('ecommerce_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('ecommerce_orders')->cascadeOnDelete();
            $table->string('status', 40)->default('preparing')->index();
            $table->string('carrier', 120)->nullable();
            $table->string('tracking_number', 120)->nullable()->index();
            $table->string('tracking_url', 500)->nullable();
            $table->unsignedInteger('package_count')->default(1);
            $table->json('address')->nullable();
            $table->timestamp('shipped_at')->nullable()->index();
            $table->timestamp('delivered_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('ecommerce_order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('ecommerce_orders')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40)->nullable();
            $table->string('from_payment_status', 40)->nullable();
            $table->string('to_payment_status', 40)->nullable();
            $table->string('from_fulfillment_status', 40)->nullable();
            $table->string('to_fulfillment_status', 40)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'created_at'], 'ecommerce_order_history_order_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_order_status_histories');
        Schema::dropIfExists('ecommerce_shipments');
        Schema::dropIfExists('ecommerce_order_transactions');
        Schema::dropIfExists('ecommerce_order_items');
        Schema::dropIfExists('ecommerce_orders');
    }
};
