<?php

namespace App\Models\Admin\BlogPost;

use App\Models\Admin\Category;
use App\Models\Admin\Gallery\Gallery;
use App\Models\Admin\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function featuredImageUrl(): ?string
    {
        return $this->featured_image_path
            ? asset('storage/' . $this->featured_image_path)
            : null;
    }

    public function categories(): MorphToMany
    {
        return $this->morphToMany(
            Category::class,
            'categorizable',     // morph name (migration'daki morphs('categorizable'))
            'categorizables',    // pivot table
            'categorizable_id',  // foreignPivotKey (BlogPost id)
            'category_id'        // relatedPivotKey (Category id)
        )
            ->withTimestamps()
            ->withTrashed(); // Category soft delete ise seçili olanları editte gösterebilmek için
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
}
