<?php

namespace App\Models\Admin\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaTranslation extends Model
{
    protected $table = 'media_translations';

    protected $fillable = [
        'media_id',
        'locale',
        'title',
        'alt',
        'caption',
        'description',
    ];

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }
}
