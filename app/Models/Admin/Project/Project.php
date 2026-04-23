<?php

namespace App\Models\Admin\Project;

use App\Models\Admin\Category;
use App\Models\Admin\Gallery\Gallery;
use App\Models\Admin\Media\Media;
use App\Models\Concerns\HasSiteLocaleTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasSiteLocaleTranslations;
    use SoftDeletes;

    protected $table = 'projects';

    protected $fillable = [
        'title',
        'slug',
        'content',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'featured_image_path',
        'status',
        'is_featured',
        'featured_at',
        'appointment_id',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'featured_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';
    public const STATUS_APPOINTMENT_PENDING = 'appointment_pending';
    public const STATUS_APPOINTMENT_SCHEDULED = 'appointment_scheduled';
    public const STATUS_APPOINTMENT_DONE = 'appointment_done';
    public const STATUS_DEV_PENDING = 'dev_pending';
    public const STATUS_DEV_IN_PROGRESS = 'dev_in_progress';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_CLOSED = 'closed';

    public const PUBLIC_STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_DELIVERED,
        self::STATUS_APPROVED,
        self::STATUS_CLOSED,
    ];

    public const STATUS_OPTIONS = [
        self::STATUS_DRAFT => [
            'label' => 'Taslak',
            'badge' => 'kt-badge kt-badge-sm kt-badge-light',
            'order' => 5,
        ],
        self::STATUS_APPOINTMENT_PENDING => [
            'label' => 'Randevu Bekliyor',
            'badge' => 'kt-badge kt-badge-sm kt-badge-light-warning',
            'order' => 10,
        ],
        self::STATUS_APPOINTMENT_SCHEDULED => [
            'label' => 'Randevu Planlandi',
            'badge' => 'kt-badge kt-badge-sm kt-badge-light-primary',
            'order' => 20,
        ],
        self::STATUS_APPOINTMENT_DONE => [
            'label' => 'Randevu Tamamlandi',
            'badge' => 'kt-badge kt-badge-sm kt-badge-light-success',
            'order' => 30,
        ],
        self::STATUS_DEV_PENDING => [
            'label' => 'Gelistirme Bekliyor',
            'badge' => 'kt-badge kt-badge-sm kt-badge-light-warning',
            'order' => 40,
        ],
        self::STATUS_DEV_IN_PROGRESS => [
            'label' => 'Gelistirme Devam',
            'badge' => 'kt-badge kt-badge-sm kt-badge-primary',
            'order' => 50,
        ],
        self::STATUS_DELIVERED => [
            'label' => 'Teslim Edildi',
            'badge' => 'kt-badge kt-badge-sm kt-badge-info',
            'order' => 60,
        ],
        self::STATUS_APPROVED => [
            'label' => 'Onaylandi',
            'badge' => 'kt-badge kt-badge-sm kt-badge-success',
            'order' => 70,
        ],
        self::STATUS_ACTIVE => [
            'label' => 'Aktif',
            'badge' => 'kt-badge kt-badge-sm kt-badge-light-success',
            'order' => 80,
        ],
        self::STATUS_CLOSED => [
            'label' => 'Kapatildi',
            'badge' => 'kt-badge kt-badge-sm kt-badge-light',
            'order' => 90,
        ],
        self::STATUS_ARCHIVED => [
            'label' => 'Arsiv',
            'badge' => 'kt-badge kt-badge-sm kt-badge-light-danger',
            'order' => 100,
        ],
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
                ->orWhere('content', 'like', "%{$term}%")
                ->orWhere('meta_title', 'like', "%{$term}%")
                ->orWhere('meta_description', 'like', "%{$term}%")
                ->orWhereHas('translations', function (Builder $translationQuery) use ($term) {
                    $translationQuery
                        ->where('title', 'like', "%{$term}%")
                        ->orWhere('slug', 'like', "%{$term}%")
                        ->orWhere('content', 'like', "%{$term}%")
                        ->orWhere('meta_title', 'like', "%{$term}%")
                        ->orWhere('meta_description', 'like', "%{$term}%");
                });
        });
    }

    public function scopeInStatus(Builder $query, ?string $status): Builder
    {
        if (!$status || $status === 'all') {
            return $query;
        }

        return $query->where('status', $status);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopePublicVisible(Builder $query): Builder
    {
        return $query->whereIn('status', self::PUBLIC_STATUSES);
    }

    public static function statusOptionsSorted(): array
    {
        $options = self::STATUS_OPTIONS;
        uasort($options, fn (array $left, array $right) => (int) ($left['order'] ?? 0) <=> (int) ($right['order'] ?? 0));

        return $options;
    }

    public static function statusLabel(?string $key): string
    {
        $resolved = $key ?: self::STATUS_DRAFT;

        return self::STATUS_OPTIONS[$resolved]['label'] ?? $resolved;
    }

    public static function statusBadgeClass(?string $key): string
    {
        $resolved = $key ?: self::STATUS_DRAFT;

        return self::STATUS_OPTIONS[$resolved]['badge'] ?? 'kt-badge kt-badge-sm kt-badge-light';
    }

    public static function statusIsPublic(?string $key): bool
    {
        return in_array((string) $key, self::PUBLIC_STATUSES, true);
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

    public function translations(): HasMany
    {
        return $this->hasMany(ProjectTranslation::class, 'project_id');
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
            ->withTimestamps()
            ->orderBy('pivot_sort_order');
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

    public function excerptPreview(int $limit = 140): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim(strip_tags((string) $this->localizedValue('content'))));

        return Str::limit($normalized, $limit);
    }

    public function hasSeoMetadata(): bool
    {
        return filled($this->meta_title) && filled($this->meta_description);
    }

    public function seoCompletenessScore(): int
    {
        $checks = [
            filled($this->title),
            filled($this->content),
            filled($this->meta_title),
            filled($this->meta_description),
            filled($this->featured_image_path) || $this->featuredMediaOne() !== null,
        ];

        $completed = collect($checks)->filter()->count();

        return (int) round(($completed / count($checks)) * 100);
    }

    public function isDraft(): bool
    {
        return ($this->status ?? self::STATUS_DRAFT) === self::STATUS_DRAFT;
    }

    public function isActive(): bool
    {
        return ($this->status ?? self::STATUS_DRAFT) === self::STATUS_ACTIVE;
    }

    public function isArchived(): bool
    {
        return ($this->status ?? self::STATUS_DRAFT) === self::STATUS_ARCHIVED;
    }
}
