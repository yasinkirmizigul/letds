<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->string('status', 30)->default('new')->after('priority')->index();
            $table->foreignId('assigned_user_id')->nullable()->after('recipient_user_id')->constrained('users')->nullOnDelete();
            $table->timestamp('due_at')->nullable()->after('read_at')->index();
            $table->timestamp('first_response_at')->nullable()->after('due_at');
            $table->timestamp('resolved_at')->nullable()->after('first_response_at')->index();
            $table->timestamp('closed_at')->nullable()->after('resolved_at')->index();
            $table->foreignId('closed_by_user_id')->nullable()->after('closed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('last_activity_at')->nullable()->after('closed_by_user_id')->index();
            $table->json('tags')->nullable()->after('preferred_channels');
            $table->text('internal_note')->nullable()->after('message');
            $table->text('resolution_note')->nullable()->after('internal_note');

            $table->index(['status', 'assigned_user_id', 'created_at'], 'contact_messages_workflow_idx');
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropIndex('contact_messages_workflow_idx');
            $table->dropForeign(['assigned_user_id']);
            $table->dropForeign(['closed_by_user_id']);
            $table->dropColumn([
                'status',
                'assigned_user_id',
                'due_at',
                'first_response_at',
                'resolved_at',
                'closed_at',
                'closed_by_user_id',
                'last_activity_at',
                'tags',
                'internal_note',
                'resolution_note',
            ]);
        });
    }
};
