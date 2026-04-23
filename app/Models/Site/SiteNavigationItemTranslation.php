<?php

namespace App\Models\Site;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteNavigationItemTranslation extends Model
{
    protected $fillable = [
        'locale',
        'title',
    ];

    public function navigationItem(): BelongsTo
    {
        return $this->belongsTo(SiteNavigationItem::class, 'site_navigation_item_id');
    }
}
