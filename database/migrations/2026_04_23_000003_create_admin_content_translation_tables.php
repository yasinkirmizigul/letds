<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('blog_post_translations')) {
            Schema::create('blog_post_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('blog_post_id')->constrained('blog_posts')->cascadeOnDelete();
                $table->string('locale', 10);
                $table->string('title')->nullable();
                $table->string('slug')->nullable();
                $table->text('excerpt')->nullable();
                $table->longText('content')->nullable();
                $table->string('meta_title')->nullable();
                $table->string('meta_description')->nullable();
                $table->string('meta_keywords', 500)->nullable();
                $table->timestamps();

                $table->unique(['blog_post_id', 'locale'], 'bp_tr_record_locale_uq');
                $table->unique(['locale', 'slug'], 'bp_tr_locale_slug_uq');
            });
        }

        if (!Schema::hasTable('project_translations')) {
            Schema::create('project_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->string('locale', 10);
                $table->string('title')->nullable();
                $table->string('slug')->nullable();
                $table->longText('content')->nullable();
                $table->string('meta_title')->nullable();
                $table->string('meta_description')->nullable();
                $table->string('meta_keywords', 500)->nullable();
                $table->timestamps();

                $table->unique(['project_id', 'locale'], 'project_tr_record_locale_uq');
                $table->unique(['locale', 'slug'], 'project_tr_locale_slug_uq');
            });
        }

        if (!Schema::hasTable('product_translations')) {
            Schema::create('product_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('locale', 10);
                $table->string('title')->nullable();
                $table->string('slug')->nullable();
                $table->longText('content')->nullable();
                $table->string('meta_title')->nullable();
                $table->string('meta_description')->nullable();
                $table->string('meta_keywords', 500)->nullable();
                $table->timestamps();

                $table->unique(['product_id', 'locale'], 'product_tr_record_locale_uq');
                $table->unique(['locale', 'slug'], 'product_tr_locale_slug_uq');
            });
        }

        if (!Schema::hasTable('category_translations')) {
            Schema::create('category_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
                $table->string('locale', 10);
                $table->string('name')->nullable();
                $table->string('slug')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();

                $table->unique(['category_id', 'locale'], 'category_tr_record_locale_uq');
                $table->unique(['locale', 'slug'], 'category_tr_locale_slug_uq');
            });
        }

        if (!Schema::hasTable('media_translations')) {
            Schema::create('media_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
                $table->string('locale', 10);
                $table->string('title')->nullable();
                $table->string('alt')->nullable();
                $table->text('caption')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();

                $table->unique(['media_id', 'locale'], 'media_tr_record_locale_uq');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('media_translations');
        Schema::dropIfExists('category_translations');
        Schema::dropIfExists('product_translations');
        Schema::dropIfExists('project_translations');
        Schema::dropIfExists('blog_post_translations');
    }
};
