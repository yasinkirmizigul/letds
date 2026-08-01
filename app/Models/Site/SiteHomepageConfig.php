<?php

namespace App\Models\Site;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SiteHomepageConfig extends Model
{
    protected $fillable = [
        'key',
        'content',
        'settings',
    ];

    protected $casts = [
        'content' => 'array',
        'settings' => 'array',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(SiteHomepageConfigTranslation::class)->orderBy('locale');
    }
}
