<?php

namespace App\Models\Admin\Project;

use App\Models\Admin\Category;
use App\Models\Admin\Gallery\Gallery;
use App\Models\Admin\Media\Media;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'keywords',
        'description',
        'featured_image_path',
        'featured_media_id',
        'status',
        'is_featured',
        'featured_at',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'featured_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // --- Status constants
    public const STATUS_APPOINTMENT_PENDING    = 'appointment_pending';
    public const STATUS_APPOINTMENT_SCHEDULED  = 'appointment_scheduled';
    public const STATUS_APPOINTMENT_DONE       = 'appointment_done';

    public const STATUS_DEV_PENDING            = 'dev_pending';
    public const STATUS_DEV_IN_PROGRESS        = 'dev_in_progress';

    public const STATUS_DELIVERED              = 'delivered';
    public const STATUS_APPROVED               = 'approved';
    public const STATUS_CLOSED                 = 'closed';

    /**
     * ✅ Single source of truth:
     * - label: UI text
     * - badge: KTUI badge class
     * - order: dropdown sorting
     */
    public const STATUS_OPTIONS = [
        self::STATUS_APPOINTMENT_PENDING => [
            'label' => 'Randevu Bekliyor',
            'badge' => 'kt-badge kt-badge-sm kt-badge-light-warning',
            'order' => 10,
        ],
        self::STATUS_APPOINTMENT_SCHEDULED => [
            'label' => 'Randevu Planlandı',
            'badge' => 'kt-badge kt-badge-sm kt-badge-light-primary',
            'order' => 20,
        ],
        self::STATUS_APPOINTMENT_DONE => [
            'label' => 'Randevu Tamamlandı',
            'badge' => 'kt-badge kt-badge-sm kt-badge-light-success',
            'order' => 30,
        ],

        self::STATUS_DEV_PENDING => [
            'label' => 'Geliştirme Bekliyor',
            'badge' => 'kt-badge kt-badge-sm kt-badge-light-warning',
            'order' => 40,
        ],
        self::STATUS_DEV_IN_PROGRESS => [
            'label' => 'Geliştirme Devam',
            'badge' => 'kt-badge kt-badge-sm kt-badge-primary',
            'order' => 50,
        ],

        self::STATUS_DELIVERED => [
            'label' => 'Teslim Edildi',
            'badge' => 'kt-badge kt-badge-sm kt-badge-info',
            'order' => 60,
        ],
        self::STATUS_APPROVED => [
            'label' => 'Onaylandı',
            'badge' => 'kt-badge kt-badge-sm kt-badge-success',
            'order' => 70,
        ],
        self::STATUS_CLOSED => [
            'label' => 'Kapatıldı',
            'badge' => 'kt-badge kt-badge-sm kt-badge-light',
            'order' => 80,
        ],
    ];

    public static function statusOptionsSorted(): array
    {
        $opts = self::STATUS_OPTIONS;
        uasort($opts, fn ($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
        return $opts;
    }

    public static function statusLabel(?string $key): string
    {
        $key = $key ?: self::STATUS_APPOINTMENT_PENDING;
        return self::STATUS_OPTIONS[$key]['label'] ?? $key;
    }

    public static function statusBadgeClass(?string $key): string
    {
        $key = $key ?: self::STATUS_APPOINTMENT_PENDING;
        return self::STATUS_OPTIONS[$key]['badge'] ?? 'kt-badge kt-badge-sm kt-badge-light';
    }

    /**
     * Categories: categorizables (morphs)
     */
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

    /**
     * Galleries: galleryables (slot + sort_order)
     */
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

    /**
     * Featured image: Media’dan seçilecek (mediables, collection=featured).
     */
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
        return $this->featuredMedia()->first();
    }

    public function featuredMediaUrl(): ?string
    {
        $m = $this->featuredMediaOne();
        if ($m) {
            return $m->url('optimized');
        }

        // legacy fallback (eski kayıtlar bozulmasın)
        return $this->featuredImageUrl();
    }

    public function featuredImageUrl(): ?string
    {
        return $this->featured_image_path
            ? asset('storage/' . $this->featured_image_path)
            : null;
    }

    // legacy helpers (istersen sonra sileriz)
    public function isDraft(): bool
    {
        return ($this->status ?? 'draft') === 'draft';
    }

    public function isActive(): bool
    {
        return ($this->status ?? 'draft') === 'active';
    }

    public function isArchived(): bool
    {
        return ($this->status ?? 'draft') === 'archived';
    }
}
