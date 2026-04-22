<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'barcode')) {
                $table->string('barcode', 100)->nullable()->unique();
            }

            if (!Schema::hasColumn('products', 'sale_price')) {
                $table->decimal('sale_price', 12, 2)->nullable();
            }

            if (!Schema::hasColumn('products', 'currency')) {
                $table->string('currency', 3)->nullable();
            }

            if (!Schema::hasColumn('products', 'vat_rate')) {
                $table->unsignedTinyInteger('vat_rate')->nullable();
            }

            if (!Schema::hasColumn('products', 'brand')) {
                $table->string('brand', 120)->nullable();
            }

            if (!Schema::hasColumn('products', 'weight')) {
                $table->decimal('weight', 10, 3)->nullable();
            }

            if (!Schema::hasColumn('products', 'width')) {
                $table->decimal('width', 10, 3)->nullable();
            }

            if (!Schema::hasColumn('products', 'height')) {
                $table->decimal('height', 10, 3)->nullable();
            }

            if (!Schema::hasColumn('products', 'length')) {
                $table->decimal('length', 10, 3)->nullable();
            }

            if (!Schema::hasColumn('products', 'is_active')) {
                $table->boolean('is_active')->default(true)->index();
            }

            if (!Schema::hasColumn('products', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->index();
            }
        });
    }

    public function down(): void
    {
        // This repair migration intentionally does not remove columns on rollback.
        // Older databases may already have these fields from the original create migration.
    }
};
