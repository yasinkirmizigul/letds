<?php

namespace App\Models\Site;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteHomepageSectionItemTranslation extends Model
{
    protected $fillable = [
        'locale',
        'title',
        'description',
        'link_label',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(SiteHomepageSectionItem::class, 'site_homepage_section_item_id');
    }
}
