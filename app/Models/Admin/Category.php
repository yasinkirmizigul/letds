<?php

namespace App\Models\Admin;

use App\Models\Admin\BlogPost\BlogPost;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphedByMany;

class Category extends Model
{
    protected $fillable = ['name','slug','parent_id'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function blogPosts(): MorphedByMany
    {
        return $this->morphedByMany(
            BlogPost::class,
            'categorizable',
            'categorizables',
            'category_id',
            'categorizable_id'
        );
    }

    // ✅ edit'te kendi altını parent seçmeyi engellemek için:
    public function descendantIds(): array
    {
        $ids = [];
        $stack = $this->children()->get(['id'])->all();

        while ($stack) {
            /** @var \App\Models\Admin\Category $node */
            $node = array_pop($stack);
            $ids[] = $node->id;

            $more = self::query()->where('parent_id', $node->id)->get(['id'])->all();
            foreach ($more as $m) $stack[] = $m;
        }

        return $ids;
    }
}
