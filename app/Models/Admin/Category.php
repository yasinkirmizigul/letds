<?php

namespace App\Models\Admin;

use App\Models\Admin\BlogPost\BlogPost;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphedByMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Category extends Model
{
    use SoftDeletes;

    protected $casts = [
        'deleted_at' => 'datetime',
    ];
    protected $fillable = ['name', 'slug', 'parent_id'];
    protected static function booted()
    {
        static::saving(function (Category $category) {

            // slug boşsa veya name değiştiyse yeniden üret
            if (blank($category->slug) || $category->isDirty('name')) {

                $base = Str::slug($category->name) ?: 'category';
                $slug = $base;
                $i = 2;

                while (static::where('slug', $slug)
                    ->when($category->exists, fn($q) => $q->where('id', '!=', $category->id))
                    ->exists()) {
                    $slug = $base . '-' . $i;
                    $i++;
                }

                $category->slug = $slug;
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function descendantIds(): array
    {
        $ids = [];
        $this->collectDescendantIds($ids);
        return $ids;
    }

    protected function collectDescendantIds(array &$ids): void
    {
        foreach ($this->children()->select('id')->get() as $child) {
            $ids[] = $child->id;
            $child->collectDescendantIds($ids);
        }
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
