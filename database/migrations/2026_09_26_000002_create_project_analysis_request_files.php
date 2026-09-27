<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_analysis_request_files', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('project_analysis_request_id');
            $table->unsignedBigInteger('project_file_id');
            $table->foreign('project_analysis_request_id', 'analysis_req_files_req_fk')->references('id')->on('project_analysis_requests')->cascadeOnDelete();
            $table->foreign('project_file_id', 'analysis_req_files_file_fk')->references('id')->on('project_files')->cascadeOnDelete();
            $table->unique(['project_analysis_request_id', 'project_file_id'], 'analysis_request_file_unique');
        });

        DB::table('project_analysis_requests')
            ->whereNotNull('project_file_id')
            ->orderBy('id')
            ->chunkById(500, function ($requests): void {
                DB::table('project_analysis_request_files')->insert($requests->map(fn ($request) => [
                    'project_analysis_request_id' => $request->id,
                    'project_file_id' => $request->project_file_id,
                ])->all());
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_analysis_request_files');
    }
};
