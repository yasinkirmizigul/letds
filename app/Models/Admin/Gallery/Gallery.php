<?php

namespace App\Models\Admin\Gallery;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Gallery extends Model
{
    use SoftDeletes;

    protected $table = 'galleries';

    protected $fillable = [
        'name','slug','description',
        'created_by','updated_by',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(GalleryItem::class, 'gallery_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public static function booted(): void
    {
        static::creating(function (self $g) {
            if (!$g->slug) {
                $g->slug = Str::slug($g->name);
            }
        });
    }
}
