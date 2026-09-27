<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_workflow_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('analysis_request_id')->nullable()->constrained('project_analysis_requests')->nullOnDelete();
            $table->string('event_type', 40);
            $table->string('actor_type', 20);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('member_seen_at')->nullable();
            $table->timestamp('provider_seen_at')->nullable();
            $table->timestamps();
            $table->index(['project_id', 'created_at']);
            $table->index(['project_id', 'member_seen_at']);
            $table->index(['project_id', 'provider_seen_at']);
        });

        $seenAt = now();
        DB::table('projects')->whereNotNull('member_id')->orderBy('id')->chunkById(500, function ($projects) use ($seenAt): void {
            DB::table('project_workflow_events')->insert($projects->map(fn ($project) => [
                'project_id' => $project->id,
                'event_type' => 'project_created',
                'actor_type' => 'system',
                'member_seen_at' => $seenAt,
                'provider_seen_at' => $seenAt,
                'created_at' => $project->created_at,
                'updated_at' => $project->created_at,
            ])->all());
        });

        DB::table('project_analysis_requests')->orderBy('id')->chunkById(500, function ($requests) use ($seenAt): void {
            DB::table('project_workflow_events')->insert($requests->map(fn ($request) => [
                'project_id' => $request->project_id,
                'analysis_request_id' => $request->id,
                'event_type' => 'request_created',
                'actor_type' => 'member',
                'actor_id' => $request->member_id,
                'body' => $request->message,
                'member_seen_at' => $seenAt,
                'provider_seen_at' => $seenAt,
                'created_at' => $request->created_at,
                'updated_at' => $request->created_at,
            ])->all());
        });

        DB::table('project_files')
            ->join('projects', 'projects.id', '=', 'project_files.project_id')
            ->whereNotNull('projects.member_id')
            ->whereNull('project_files.member_id')
            ->where('project_files.note', 'Analiz raporu')
            ->select(['project_files.id', 'project_files.project_id', 'project_files.created_at'])
            ->orderBy('project_files.id')
            ->chunkById(500, function ($reports) use ($seenAt): void {
                DB::table('project_workflow_events')->insert($reports->map(fn ($report) => [
                    'project_id' => $report->project_id,
                    'event_type' => 'report_delivered',
                    'actor_type' => 'system',
                    'data' => json_encode(['file_ids' => [$report->id]]),
                    'member_seen_at' => $seenAt,
                    'provider_seen_at' => $seenAt,
                    'created_at' => $report->created_at,
                    'updated_at' => $report->created_at,
                ])->all());
            }, 'project_files.id', 'id');
    }

    public function down(): void
    {
        Schema::dropIfExists('project_workflow_events');
    }
};
