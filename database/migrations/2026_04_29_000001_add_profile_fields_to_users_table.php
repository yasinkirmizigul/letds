<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 40)->nullable();
            }

            if (!Schema::hasColumn('users', 'company')) {
                $table->string('company')->nullable();
            }

            if (!Schema::hasColumn('users', 'location')) {
                $table->string('location')->nullable();
            }

            if (!Schema::hasColumn('users', 'website_url')) {
                $table->string('website_url', 500)->nullable();
            }

            if (!Schema::hasColumn('users', 'linkedin_url')) {
                $table->string('linkedin_url', 500)->nullable();
            }

            if (!Schema::hasColumn('users', 'bio')) {
                $table->text('bio')->nullable();
            }

            if (!Schema::hasColumn('users', 'skills')) {
                $table->json('skills')->nullable();
            }
        });
    }

    public function down(): void
    {
        $columns = array_values(array_filter([
            'phone',
            'company',
            'location',
            'website_url',
            'linkedin_url',
            'bio',
            'skills',
        ], fn (string $column): bool => Schema::hasColumn('users', $column)));

        if ($columns !== []) {
            Schema::table('users', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
