<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->string('slug')->unique();

            $table->longText('content')->nullable();
            $table->text('excerpt')->nullable();

            $table->string('meta_title', 255)->nullable();
            $table->string('meta_keywords', 500)->nullable();
            $table->string('meta_description', 255)->nullable();

            $table->string('featured_image_path', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->timestamp('featured_at')->nullable();

            $table->index(['is_featured','featured_at'], 'blog_posts_featured_idx');
            $table->timestamp('published_at')->nullable();
            $table->index(['is_published', 'published_at'], 'blog_posts_published_idx');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
