<?php

namespace App\Models\Site;

use App\Models\Concerns\HasSiteLocaleTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SiteHomepageSection extends Model
{
    use HasSiteLocaleTranslations;

    protected $fillable = [
        'type',
        'placement',
        'eyebrow',
        'title',
        'description',
        'settings',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(SiteHomepageSectionTranslation::class)->orderBy('locale');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SiteHomepageSectionItem::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForPlacement(Builder $query, string $placement): Builder
    {
        return $query->where('placement', $placement);
    }

    public function localized(string $field, ?string $locale = null, mixed $fallback = null): mixed
    {
        return $this->localizedValue($field, $locale, $fallback);
    }
}
