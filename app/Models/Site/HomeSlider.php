<?php

namespace App\Models\Site;

use App\Models\Admin\Media\Media;
use App\Models\Concerns\HasSiteLocaleTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HomeSlider extends Model
{
    use HasSiteLocaleTranslations;

    public const THEME_DARK = 'dark';
    public const THEME_LIGHT = 'light';
    public const THEME_BRAND = 'brand';

    protected $fillable = [
        'badge',
        'title',
        'subtitle',
        'body',
        'cta_label',
        'cta_url',
        'image_media_id',
        'image_path',
        'crop_x',
        'crop_y',
        'crop_zoom',
        'overlay_strength',
        'theme',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'crop_x' => 'float',
        'crop_y' => 'float',
        'crop_zoom' => 'float',
        'overlay_strength' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function imageMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_media_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(HomeSliderTranslation::class)->orderBy('locale');
    }

    public function imageUrl(): ?string
    {
        if ($this->imageMedia) {
            return $this->imageMedia->url('optimized');
        }

        return $this->image_path
            ? asset('storage/' . $this->image_path)
            : null;
    }

    public static function themeOptions(): array
    {
        return [
            self::THEME_DARK => 'Koyu vurgu',
            self::THEME_LIGHT => 'Açık vurgu',
            self::THEME_BRAND => 'Marka vurgu',
        ];
    }

    public function frameStyle(): string
    {
        $positionX = number_format((float) ($this->crop_x ?? 50), 2, '.', '');
        $positionY = number_format((float) ($this->crop_y ?? 50), 2, '.', '');
        $zoom = number_format(max(1, (float) ($this->crop_zoom ?? 1)), 2, '.', '');

        return sprintf('object-position:%s%% %s%%; transform:scale(%s);', $positionX, $positionY, $zoom);
    }

    public function localized(string $field, ?string $locale = null, mixed $fallback = null): mixed
    {
        return $this->localizedValue($field, $locale, $fallback);
    }
}
