<?php

namespace App\Models\Site;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteCounterTranslation extends Model
{
    protected $fillable = [
        'locale',
        'label',
        'prefix',
        'suffix',
        'description',
    ];

    public function counter(): BelongsTo
    {
        return $this->belongsTo(SiteCounter::class, 'site_counter_id');
    }
}
