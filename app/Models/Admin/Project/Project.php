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
    public const STATUS_APPOINTMENT_PENDING   = 'appointment_pending';
    public const STATUS_APPOINTMENT_SCHEDULED = 'appointment_scheduled';
    public const STATUS_APPOINTMENT_DONE      = 'appointment_done';

    public const STATUS_DEV_PENDING           = 'dev_pending';
    public const STATUS_DEV_IN_PROGRESS       = 'dev_in_progress';

    public const STATUS_DELIVERED             = 'delivered';
    public const STATUS_APPROVED              = 'approved';
    public const STATUS_CLOSED                = 'closed';

    public const STATUS_OPTIONS = [
        self::STATUS_APPOINTMENT_PENDING   => 'Randevu Bekliyor',
        self::STATUS_APPOINTMENT_SCHEDULED => 'Randevu Planlandı',
        self::STATUS_APPOINTMENT_DONE      => 'Randevu Tamamlandı',

        self::STATUS_DEV_PENDING           => 'Geliştirme Bekliyor',
        self::STATUS_DEV_IN_PROGRESS       => 'Geliştirme Devam',

        self::STATUS_DELIVERED             => 'Teslim Edildi',
        self::STATUS_APPROVED              => 'Onaylandı',
        self::STATUS_CLOSED                => 'Kapatıldı',
    ];

    /**
     * Categories: categorizables (morphs)
     * Migration: category_id + morphs('categorizable') :contentReference[oaicite:1]{index=1}
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
     * BlogPost modelindeki pattern ile aynı. :contentReference[oaicite:2]{index=2}
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
     * Schema: media_id + morphs('mediable') + collection + order :contentReference[oaicite:3]{index=3}
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
    public function featuredImageUrl(): ?string
    {
        return $this->featured_image_path
            ? asset('storage/' . $this->featured_image_path)
            : null;
    }
    public static function statusLabel(?string $key): string
    {
        $key = $key ?: self::STATUS_APPOINTMENT_PENDING;
        return self::STATUS_OPTIONS[$key] ?? $key;
    }

    public static function statusBadgeClass(?string $key): string
    {
        return match ($key) {
            self::STATUS_APPOINTMENT_PENDING   => 'kt-badge kt-badge-sm kt-badge-light-warning',
            self::STATUS_APPOINTMENT_SCHEDULED => 'kt-badge kt-badge-sm kt-badge-light-primary',
            self::STATUS_APPOINTMENT_DONE      => 'kt-badge kt-badge-sm kt-badge-light-success',

            self::STATUS_DEV_PENDING           => 'kt-badge kt-badge-sm kt-badge-light-warning',
            self::STATUS_DEV_IN_PROGRESS       => 'kt-badge kt-badge-sm kt-badge-primary',

            self::STATUS_DELIVERED             => 'kt-badge kt-badge-sm kt-badge-info',
            self::STATUS_APPROVED              => 'kt-badge kt-badge-sm kt-badge-success',
            self::STATUS_CLOSED                => 'kt-badge kt-badge-sm kt-badge-light',
            default                            => 'kt-badge kt-badge-sm kt-badge-light',
        };
    }
}
