<?php

namespace App\Models\Site;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeSliderTranslation extends Model
{
    protected $fillable = [
        'locale',
        'badge',
        'title',
        'subtitle',
        'body',
        'cta_label',
        'cta_url',
    ];

    public function slider(): BelongsTo
    {
        return $this->belongsTo(HomeSlider::class, 'home_slider_id');
    }
}
