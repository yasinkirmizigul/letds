<?php

namespace App\Models\Site;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteSettingTranslation extends Model
{
    protected $fillable = [
        'locale',
        'site_name',
        'site_tagline',
        'hero_notice',
        'address_line',
        'map_title',
        'office_hours',
        'footer_note',
        'member_terms_title',
        'member_terms_summary',
        'member_terms_content',
        'under_construction_title',
        'under_construction_message',
        'ui_lines',
    ];

    protected $casts = [
        'ui_lines' => 'array',
    ];

    public function setting(): BelongsTo
    {
        return $this->belongsTo(SiteSetting::class, 'site_setting_id');
    }
}
