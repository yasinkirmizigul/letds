<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('galleries', function (Blueprint $table): void {
            $table->boolean('is_public')->default(false)->index();
            $table->timestamp('published_at')->nullable()->index();
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->foreignId('member_id')
                ->nullable()
                ->constrained('members')
                ->nullOnDelete();
        });

        Schema::create('project_files', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('disk', 40)->default('local');
            $table->string('path', 500);
            $table->string('original_name');
            $table->string('mime_type', 160)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('note', 500)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['project_id', 'created_at']);
            $table->index(['member_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_files');

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('member_id');
        });

        Schema::table('galleries', function (Blueprint $table): void {
            $table->dropColumn(['is_public', 'published_at']);
        });
    }
};
