<?php

namespace App\Models\Site;

use App\Models\Concerns\HasSiteLocaleTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SiteCounter extends Model
{
    use HasSiteLocaleTranslations;

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

    public function translations(): HasMany
    {
        return $this->hasMany(SiteCounterTranslation::class)->orderBy('locale');
    }

    public function displayValue(): string
    {
        return trim(($this->localizedValue('prefix') ?: '') . number_format((int) $this->value) . ($this->localizedValue('suffix') ?: ''));
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
