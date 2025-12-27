<?php

namespace App\Models\Admin\Gallery;

use App\Models\Admin\Media\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GalleryItem extends Model
{
    protected $table = 'gallery_items';

    protected $fillable = [
        'gallery_id','media_id','sort_order',
        'caption','alt','link_url','link_target',
    ];

    public function gallery(): BelongsTo
    {
        return $this->belongsTo(Gallery::class, 'gallery_id');
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }
}
