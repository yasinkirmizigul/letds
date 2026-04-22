<?php

namespace App\Models\Site;

use App\Models\Admin\Media\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SitePage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'hero_kicker',
        'excerpt',
        'content',
        'icon_class',
        'featured_media_id',
        'featured_image_path',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'show_faqs',
        'show_counters',
        'is_featured',
        'is_active',
        'sort_order',
        'published_at',
    ];

    protected $casts = [
        'show_faqs' => 'boolean',
        'show_counters' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
        'deleted_at' => 'datetime',
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
                ->orWhere('hero_kicker', 'like', "%{$term}%")
                ->orWhere('excerpt', 'like', "%{$term}%")
                ->orWhere('content', 'like', "%{$term}%");
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopePublishedVisible(Builder $query): Builder
    {
        return $query
            ->active()
            ->where(function (Builder $builder) {
                $builder
                    ->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(SiteFaq::class)->orderBy('sort_order')->orderBy('id');
    }

    public function counters(): HasMany
    {
        return $this->hasMany(SiteCounter::class)->orderBy('sort_order')->orderBy('id');
    }

    public function featuredUrl(): ?string
    {
        if ($this->featuredMedia) {
            return $this->featuredMedia->url('optimized');
        }

        return $this->featured_image_path
            ? asset('storage/' . $this->featured_image_path)
            : null;
    }

    public function excerptPreview(int $limit = 160): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim(strip_tags((string) ($this->excerpt ?: $this->content))));

        return Str::limit((string) $normalized, $limit);
    }

    public function contentWordCount(): int
    {
        preg_match_all('/[\pL\pN]+/u', strip_tags((string) $this->content), $matches);

        return count($matches[0] ?? []);
    }

    public function seoCompletenessScore(): int
    {
        $checks = [
            filled($this->title),
            filled($this->content),
            filled($this->meta_title),
            filled($this->meta_description),
            filled($this->featured_media_id) || filled($this->featured_image_path),
        ];

        return (int) round((collect($checks)->filter()->count() / count($checks)) * 100);
    }

    public function publicUrl(): string
    {
        return route('site.pages.show', $this->slug);
    }

    public function readingTimeMinutes(int $wordsPerMinute = 220): int
    {
        $words = $this->contentWordCount();

        if ($words <= 0) {
            return 0;
        }

        return max(1, (int) ceil($words / max(1, $wordsPerMinute)));
    }

    public function isPublished(): bool
    {
        /** @var Carbon|null $publishedAt */
        $publishedAt = $this->published_at;

        return $this->is_active && ($publishedAt === null || $publishedAt->lte(now()));
    }
}
