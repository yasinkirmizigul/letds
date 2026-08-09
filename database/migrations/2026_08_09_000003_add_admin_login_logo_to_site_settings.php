<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('admin_login_logo_media_id')
                ->nullable()
                ->after('site_tagline');

            $table->foreign('admin_login_logo_media_id', 'site_settings_admin_login_logo_fk')
                ->references('id')
                ->on('media')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropForeign('site_settings_admin_login_logo_fk');
            $table->dropColumn('admin_login_logo_media_id');
        });
    }
};
