<?php

namespace App\Models\Site;

use App\Models\Concerns\HasSiteLocaleTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SiteHomepageSectionItem extends Model
{
    use HasSiteLocaleTranslations;

    protected $fillable = [
        'site_homepage_section_id',
        'title',
        'description',
        'icon',
        'link_label',
        'link_url',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(SiteHomepageSection::class, 'site_homepage_section_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(SiteHomepageSectionItemTranslation::class)->orderBy('locale');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function localized(string $field, ?string $locale = null, mixed $fallback = null): mixed
    {
        return $this->localizedValue($field, $locale, $fallback);
    }
}
