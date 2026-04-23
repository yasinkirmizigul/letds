<?php

namespace App\Models\Site;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteFaqTranslation extends Model
{
    protected $fillable = [
        'locale',
        'group_label',
        'question',
        'answer',
    ];

    public function faq(): BelongsTo
    {
        return $this->belongsTo(SiteFaq::class, 'site_faq_id');
    }
}
