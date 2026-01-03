<?php

namespace App\Models\Admin\Project;

use App\Models\Admin\Category;
use App\Models\Admin\Gallery\Gallery;
use App\Models\Admin\Media\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'status',
        'appointment_id',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
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
        if (!$m) return null;

        return $m->url('optimized');
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
}
