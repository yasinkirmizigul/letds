<?php

namespace App\Models\Site;

use App\Models\Concerns\HasSiteLocaleTranslations;
use App\Support\Site\NavigationTree;
use App\Support\Site\SiteLocalization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class SiteNavigationItem extends Model
{
    use HasSiteLocaleTranslations;

    public const LOCATION_PRIMARY = 'primary';
    public const LOCATION_FOOTER = 'footer';

    public const LINK_TYPE_PAGE = 'page';
    public const LINK_TYPE_CUSTOM = 'custom';

    public const TARGET_SELF = '_self';
    public const TARGET_BLANK = '_blank';

    protected $fillable = [
        'location',
        'parent_id',
        'site_page_id',
        'title',
        'icon_class',
        'link_type',
        'url',
        'target',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForLocation(Builder $query, string $location): Builder
    {
        return $query->where('location', $location);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(SitePage::class, 'site_page_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(SiteNavigationItemTranslation::class)->orderBy('locale');
    }

    public function resolvedUrl(?string $locale = null): string
    {
        $locale = $locale ?: SiteLocalization::currentLocale();

        if ($this->link_type === self::LINK_TYPE_PAGE && $this->page?->slug) {
            return $this->page->publicUrl($locale);
        }

        return $this->url ?: '#';
    }

    public static function locationOptions(): array
    {
        return [
            self::LOCATION_PRIMARY => 'Üst Menü',
            self::LOCATION_FOOTER => 'Alt Menü',
        ];
    }

    public static function linkTypeOptions(): array
    {
        return [
            self::LINK_TYPE_PAGE => 'İçerik Sayfası',
            self::LINK_TYPE_CUSTOM => 'Özel URL',
        ];
    }

    public static function targetOptions(): array
    {
        return [
            self::TARGET_SELF => 'Aynı sekmede aç',
            self::TARGET_BLANK => 'Yeni sekmede aç',
        ];
    }

    public static function treeForLocation(string $location, bool $activeOnly = false): Collection
    {
        return NavigationTree::forLocation($location, $activeOnly);
    }

    public function localized(string $field, ?string $locale = null, mixed $fallback = null): mixed
    {
        return $this->localizedValue($field, $locale, $fallback);
    }
}
