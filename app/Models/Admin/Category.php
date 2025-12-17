<?php

namespace App\Models\Admin;

use App\Models\Admin\BlogPost\BlogPost;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'parent_id',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function blogPosts(): MorphToMany
    {
        return $this->morphedByMany(BlogPost::class, 'categorizable');
    }
}
