<?php

namespace App\Models\Concerns;

use App\Support\Site\SiteLocalization;
use Illuminate\Database\Eloquent\Model;

trait HasSiteLocaleTranslations
{
    public function translationFor(?string $locale = null): ?Model
    {
        $locale = $locale ?: SiteLocalization::currentLocale();

        if (SiteLocalization::isDefault($locale)) {
            return null;
        }

        if ($this->relationLoaded('translations')) {
            return $this->translations->firstWhere('locale', $locale);
        }

        return $this->translations()
            ->where('locale', $locale)
            ->first();
    }

    public function localizedValue(string $field, ?string $locale = null, mixed $fallback = null): mixed
    {
        $locale = $locale ?: SiteLocalization::currentLocale();

        if (!SiteLocalization::isDefault($locale)) {
            $translation = $this->translationFor($locale);
            $translated = $translation?->{$field};

            if (filled($translated)) {
                return $translated;
            }
        }

        $value = $this->getRawOriginal($field);

        return filled($value) ? $value : $fallback;
    }
}
