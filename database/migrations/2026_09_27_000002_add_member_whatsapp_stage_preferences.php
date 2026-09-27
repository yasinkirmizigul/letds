<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->timestamp('whatsapp_stage_opted_in_at')->nullable();
            $table->string('whatsapp_stage_opted_in_phone', 20)->nullable();
        });

        Schema::table('project_workflow_events', function (Blueprint $table): void {
            $table->timestamp('whatsapp_stage_accepted_at')->nullable();
            $table->string('whatsapp_stage_message_id', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('project_workflow_events', function (Blueprint $table): void {
            $table->dropColumn(['whatsapp_stage_accepted_at', 'whatsapp_stage_message_id']);
        });

        Schema::table('members', function (Blueprint $table): void {
            $table->dropColumn(['whatsapp_stage_opted_in_at', 'whatsapp_stage_opted_in_phone']);
        });
    }
};
