<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->string('action', 128)->change();
        });
    }

    public function down(): void
    {
        if (DB::table('audit_logs')->whereRaw('LENGTH(action) > 32')->exists()) {
            throw new RuntimeException('Audit action values longer than 32 characters prevent a safe rollback.');
        }

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->string('action', 32)->change();
        });
    }
};
