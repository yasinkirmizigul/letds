<?php

namespace App\Models\Admin\Product;

use App\Models\Admin\Category;
use App\Models\Admin\Media\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'title',
        'slug',
        'content',
        'sku',
        'price',
        'stock',
        'barcode',
        'sale_price',
        'currency',
        'vat_rate',
        'brand',
        'weight',
        'width',
        'height',
        'length',
        'is_active',
        'sort_order',
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
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'weight' => 'decimal:3',
        'width' => 'decimal:3',
        'height' => 'decimal:3',
        'length' => 'decimal:3',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'featured_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_APPOINTMENT_PENDING = 'appointment_pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUS_OPTIONS = [
        self::STATUS_APPOINTMENT_PENDING => [
            'label' => 'Randevu Bekliyor',
            'badge' => 'kt-badge kt-badge-sm kt-badge-light-warning',
            'order' => 10,
        ],
        self::STATUS_DRAFT => [
            'label' => 'Taslak',
            'badge' => 'kt-badge kt-badge-sm kt-badge-light',
            'order' => 20,
        ],
        self::STATUS_ACTIVE => [
            'label' => 'Aktif',
            'badge' => 'kt-badge kt-badge-sm kt-badge-light-success',
            'order' => 30,
        ],
        self::STATUS_ARCHIVED => [
            'label' => 'Arsiv',
            'badge' => 'kt-badge kt-badge-sm kt-badge-light-danger',
            'order' => 40,
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
                ->orWhere('sku', 'like', "%{$term}%")
                ->orWhere('barcode', 'like', "%{$term}%")
                ->orWhere('brand', 'like', "%{$term}%")
                ->orWhere('meta_title', 'like', "%{$term}%");
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

    public function scopeLowStock(Builder $query, int $threshold = 5): Builder
    {
        return $query
            ->whereNotNull('stock')
            ->where('stock', '<=', max(0, $threshold));
    }

    public static function statusOptionsSorted(): array
    {
        $options = self::STATUS_OPTIONS;
        uasort($options, fn (array $left, array $right) => (int) ($left['order'] ?? 0) <=> (int) ($right['order'] ?? 0));

        return $options;
    }

    public static function statusLabel(?string $key): string
    {
        $resolved = $key ?: self::STATUS_APPOINTMENT_PENDING;

        return self::STATUS_OPTIONS[$resolved]['label'] ?? $resolved;
    }

    public static function statusBadgeClass(?string $key): string
    {
        $resolved = $key ?: self::STATUS_APPOINTMENT_PENDING;

        return self::STATUS_OPTIONS[$resolved]['badge'] ?? 'kt-badge kt-badge-sm kt-badge-light';
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product', 'product_id', 'category_id');
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

    public function estimatedReadTimeMinutes(int $wordsPerMinute = 220): int
    {
        $words = $this->contentWordCount();
        if ($words === 0) {
            return 0;
        }

        return max(1, (int) ceil($words / max(1, $wordsPerMinute)));
    }

    public function excerptPreview(int $limit = 140): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim(strip_tags((string) $this->content)));

        return Str::limit((string) $normalized, $limit);
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
}
