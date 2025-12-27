<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {

            if (!Schema::hasColumn('audit_logs', 'is_system')) {
                $table->boolean('is_system')
                    ->default(false)
                    ->after('user_id')
                    ->comment('SYSTEM / CLI log mu?');
            }

            if (!Schema::hasColumn('audit_logs', 'method')) {
                $table->string('method', 16)
                    ->nullable()
                    ->after('is_system')
                    ->comment('HTTP method veya CLI');
            }

            if (!Schema::hasColumn('audit_logs', 'user_agent')) {
                $table->string('user_agent', 255)
                    ->nullable()
                    ->after('method')
                    ->comment('Browser UA veya CLI');
            }
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {

            if (Schema::hasColumn('audit_logs', 'user_agent')) {
                $table->dropColumn('user_agent');
            }

            if (Schema::hasColumn('audit_logs', 'method')) {
                $table->dropColumn('method');
            }

            if (Schema::hasColumn('audit_logs', 'is_system')) {
                $table->dropColumn('is_system');
            }
        });
    }
};
