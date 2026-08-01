<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_review_questions', function (Blueprint $table) {
            $table->id();
            $table->string('question', 500);
            $table->string('type', 40)->default('scale');
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('service_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('provider_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reviewable_type', 160);
            $table->unsignedBigInteger('reviewable_id');
            $table->string('service_type', 40)->index();
            $table->string('service_title');
            $table->string('service_reference', 120)->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->unsignedTinyInteger('overall_rating')->nullable()->index();
            $table->text('public_comment')->nullable();
            $table->timestamp('service_completed_at')->nullable()->index();
            $table->timestamp('invited_at')->nullable()->index();
            $table->timestamp('questions_locked_at')->nullable();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamps();

            $table->unique(
                ['reviewable_type', 'reviewable_id'],
                'service_reviews_reviewable_unique'
            );
            $table->index(['member_id', 'status'], 'service_reviews_member_status_idx');
            $table->index(['provider_user_id', 'status'], 'service_reviews_provider_status_idx');
        });

        Schema::create('service_review_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_review_id')->constrained('service_reviews')->cascadeOnDelete();
            $table->foreignId('question_id')->nullable()->constrained('service_review_questions')->nullOnDelete();
            $table->string('question_text', 500);
            $table->string('question_type', 40);
            $table->json('question_options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('answer')->nullable();
            $table->timestamps();

            $table->index(['service_review_id', 'sort_order'], 'service_review_items_review_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_review_items');
        Schema::dropIfExists('service_reviews');
        Schema::dropIfExists('service_review_questions');
    }
};
