<?php

namespace App\Models\Admin\BlogPost;

use App\Models\Admin\Category;
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
        'content',
        'meta_keywords',
        'meta_description',
        'featured_image_path',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Görsel URL helper
    public function featuredImageUrl(): ?string
    {
        return $this->featured_image_path
            ? asset('storage/' . $this->featured_image_path)
            : null;
    }

    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categorizable', 'categorizables', 'categorizable_id', 'category_id')
            ->withTimestamps();
    }
}
