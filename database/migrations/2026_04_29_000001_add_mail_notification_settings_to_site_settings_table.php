<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('mail_notifications_enabled')->default(false);
            $table->boolean('notify_contact_messages')->default(true);
            $table->boolean('notify_appointments')->default(true);
            $table->string('mail_from_address')->nullable();
            $table->string('mail_from_name')->nullable();
            $table->string('smtp_host')->nullable();
            $table->unsignedInteger('smtp_port')->nullable();
            $table->string('smtp_scheme', 20)->nullable();
            $table->string('smtp_username')->nullable();
            $table->text('smtp_password')->nullable();
            $table->unsignedInteger('smtp_timeout')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'mail_notifications_enabled',
                'notify_contact_messages',
                'notify_appointments',
                'mail_from_address',
                'mail_from_name',
                'smtp_host',
                'smtp_port',
                'smtp_scheme',
                'smtp_username',
                'smtp_password',
                'smtp_timeout',
            ]);
        });
    }
};
