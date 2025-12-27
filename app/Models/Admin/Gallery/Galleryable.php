<?php

namespace App\Models\Admin\Gallery;

use Illuminate\Database\Eloquent\Model;

class Galleryable extends Model
{
    protected $table = 'galleryables';

    protected $fillable = [
        'gallery_id',
        'galleryable_type','galleryable_id',
        'slot','sort_order',
    ];
}
