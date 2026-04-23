<?php

namespace App\Models\Site;

use App\Models\Concerns\HasSiteLocaleTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SiteFaq extends Model
{
    use HasSiteLocaleTranslations;

    protected $fillable = [
        'site_page_id',
        'group_label',
        'question',
        'answer',
        'icon_class',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(SitePage::class, 'site_page_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(SiteFaqTranslation::class)->orderBy('locale');
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
