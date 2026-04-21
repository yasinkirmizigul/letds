<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            if (!Schema::hasColumn('blog_posts', 'meta_title')) {
                $table->string('meta_title', 255)->nullable()->after('excerpt');
            }

            if (!Schema::hasColumn('blog_posts', 'created_by')) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->after('meta_keywords')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('blog_posts', 'updated_by')) {
                $table->foreignId('updated_by')
                    ->nullable()
                    ->after('created_by')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            $table->index(['is_published', 'published_at'], 'blog_posts_published_idx');
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            if (Schema::hasColumn('blog_posts', 'updated_by')) {
                $table->dropConstrainedForeignId('updated_by');
            }

            if (Schema::hasColumn('blog_posts', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }

            if (Schema::hasColumn('blog_posts', 'meta_title')) {
                $table->dropColumn('meta_title');
            }

            $table->dropIndex('blog_posts_published_idx');
        });
    }
};
