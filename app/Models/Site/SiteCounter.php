<?php

namespace App\Models\Site;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteCounter extends Model
{
    protected $fillable = [
        'site_page_id',
        'label',
        'value',
        'prefix',
        'suffix',
        'description',
        'icon_class',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'value' => 'integer',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(SitePage::class, 'site_page_id');
    }

    public function displayValue(): string
    {
        return trim(($this->prefix ?: '') . number_format((int) $this->value) . ($this->suffix ?: ''));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
