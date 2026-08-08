<?php

namespace App\Models\Site;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteHomepageSectionTranslation extends Model
{
    protected $fillable = [
        'locale',
        'eyebrow',
        'title',
        'description',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(SiteHomepageSection::class, 'site_homepage_section_id');
    }
}
