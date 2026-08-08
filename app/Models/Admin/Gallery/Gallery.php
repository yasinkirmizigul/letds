<?php

namespace App\Models\Admin\Gallery;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Gallery extends Model
{
    use SoftDeletes;

    protected $table = 'galleries';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_public',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(GalleryItem::class, 'gallery_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function coverItem(): HasOne
    {
        return $this->hasOne(GalleryItem::class, 'gallery_id')
            ->whereHas('media', fn (Builder $query) => $query->where('mime_type', 'like', 'image/%'))
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->where('is_public', true)
            ->where(function (Builder $builder): void {
                $builder
                    ->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    protected static function booted(): void
    {
        static::creating(function (self $g) {
            if (! $g->slug) {
                $g->slug = Str::slug($g->name);
            }
        });

        static::updating(function (self $g) {
            // slug boş bırakılırsa name'den üret (update sırasında da)
            if (! $g->slug) {
                $g->slug = Str::slug($g->name);
            }
        });
    }
}
