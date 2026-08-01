<?php

namespace App\Models\Site;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteHomepageConfigTranslation extends Model
{
    protected $fillable = [
        'locale',
        'content',
    ];

    protected $casts = [
        'content' => 'array',
    ];

    public function homepageConfig(): BelongsTo
    {
        return $this->belongsTo(SiteHomepageConfig::class, 'site_homepage_config_id');
    }
}
