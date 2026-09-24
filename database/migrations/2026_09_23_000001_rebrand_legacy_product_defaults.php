<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('site_settings')) {
            DB::table('site_settings')
                ->where('site_name', 'Laravel')
                ->update(['site_name' => 'PROBABLUE']);

            if (Schema::hasColumn('site_settings', 'mail_from_name')) {
                DB::table('site_settings')
                    ->whereIn('mail_from_name', ['Laravel', 'Laravel Log'])
                    ->update(['mail_from_name' => 'PROBABLUE']);
            }
        }

        if (Schema::hasTable('site_setting_translations')) {
            DB::table('site_setting_translations')
                ->where('site_name', 'Laravel')
                ->update(['site_name' => 'PROBABLUE']);
        }
    }

    public function down(): void
    {
        // Legacy placeholder values are intentionally not restored.
    }
};
