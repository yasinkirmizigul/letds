<?php

namespace App\Models\Admin\BlogPost;

use App\Models\Admin\Category;
use App\Models\Admin\Gallery\Gallery;
use App\Models\Admin\Media\Media;
use App\Models\Admin\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    use SoftDeletes;

    protected $table = 'blog_posts';

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'is_published',
        'published_at',
        'is_featured',
        'featured_at',
        'featured_image_path',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
        'featured_at' => 'datetime',
    ];

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($term) {
            $builder
                ->where('title', 'like', "%{$term}%")
                ->orWhere('slug', 'like', "%{$term}%")
                ->orWhere('excerpt', 'like', "%{$term}%")
                ->orWhere('meta_title', 'like', "%{$term}%")
                ->orWhere('meta_description', 'like', "%{$term}%");
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('is_published', false);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function featuredMedia(): MorphToMany
    {
        return $this->morphToMany(
            Media::class,
            'mediable',
            'mediables',
            'mediable_id',
            'media_id'
        )
            ->withPivot(['collection', 'order'])
            ->withTimestamps()
            ->wherePivot('collection', 'featured')
            ->orderBy('pivot_order');
    }

    public function featuredMediaOne(): ?Media
    {
        if ($this->relationLoaded('featuredMedia')) {
            return $this->featuredMedia
                ->sortBy(fn (Media $media) => (int) ($media->pivot->order ?? 0))
                ->first();
        }

        return $this->featuredMedia()->first();
    }

    public function featuredMediaUrl(): ?string
    {
        $media = $this->featuredMediaOne();
        if ($media) {
            return $media->url('optimized');
        }

        return $this->featuredImageUrl();
    }

    public function categories(): MorphToMany
    {
        return $this->morphToMany(
            Category::class,
            'categorizable',
            'categorizables',
            'categorizable_id',
            'category_id'
        )
            ->withTimestamps()
            ->withTrashed();
    }

    public function galleries(): MorphToMany
    {
        return $this->morphToMany(
            Gallery::class,
            'galleryable',
            'galleryables',
            'galleryable_id',
            'gallery_id'
        )
            ->withPivot(['slot', 'sort_order'])
            ->orderBy('pivot_sort_order');
    }

    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->featuredImageUrl();
    }

    public function featuredImageUrl(): ?string
    {
        return $this->featured_image_path
            ? asset('storage/' . $this->featured_image_path)
            : null;
    }

    public function excerptPreview(int $limit = 140): string
    {
        $source = $this->excerpt ?: strip_tags((string) $this->content);
        $normalized = preg_replace('/\s+/u', ' ', trim((string) $source));

        return Str::limit($normalized, $limit);
    }

    public function contentWordCount(): int
    {
        $content = strip_tags((string) $this->content);
        preg_match_all('/[\pL\pN]+/u', $content, $matches);

        return count($matches[0] ?? []);
    }

    public function estimatedReadTimeMinutes(int $wordsPerMinute = 200): int
    {
        $words = $this->contentWordCount();

        if ($words === 0) {
            return 0;
        }

        return max(1, (int) ceil($words / max(1, $wordsPerMinute)));
    }

    public function hasSeoMetadata(): bool
    {
        return filled($this->meta_title) && filled($this->meta_description);
    }

    public function seoCompletenessScore(): int
    {
        $checks = [
            filled($this->title),
            filled($this->excerpt),
            filled($this->meta_title),
            filled($this->meta_description),
            filled($this->featured_image_path) || $this->featuredMediaOne() !== null,
        ];

        $completed = collect($checks)->filter()->count();

        return (int) round(($completed / count($checks)) * 100);
    }
}
