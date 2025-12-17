<?php

namespace App\Models\Admin;

use App\Models\Admin\BlogPost\BlogPost;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphedByMany;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'parent_id'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function blogPosts()
    {
        return $this->morphedByMany(
            BlogPost::class,
            'categorizable',
            'categorizables',
            'category_id',
            'categorizable_id'
        );
    }
}
