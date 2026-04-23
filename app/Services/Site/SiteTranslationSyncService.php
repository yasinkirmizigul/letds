<?php

namespace App\Services\Site;

use App\Models\Site\SiteLanguage;
use App\Support\Site\SiteLocalization;
use Illuminate\Database\Eloquent\Model;

class SiteTranslationSyncService
{
    public function sync(Model $model, string $relationName, array $translations, array $fields): void
    {
        $defaultLocale = SiteLocalization::defaultLocale();
        $allowedLocales = SiteLanguage::query()->pluck('code')->all();
        $relation = $model->{$relationName}();

        foreach ($translations as $locale => $values) {
            $locale = trim((string) $locale);

            if (
                $locale === ''
                || $locale === $defaultLocale
                || !in_array($locale, $allowedLocales, true)
                || !is_array($values)
            ) {
                continue;
            }

            $payload = [];

            foreach ($fields as $field) {
                if (!array_key_exists($field, $values)) {
                    continue;
                }

                $payload[$field] = $this->normalize($values[$field]);
            }

            if (!$this->hasMeaningfulContent($payload)) {
                $relation->where('locale', $locale)->delete();

                continue;
            }

            $relation->updateOrCreate(
                ['locale' => $locale],
                array_merge($payload, ['locale' => $locale])
            );
        }
    }

    public function hasMeaningfulContent(array $payload): bool
    {
        foreach ($payload as $value) {
            if (is_array($value) && $this->hasMeaningfulContent($value)) {
                return true;
            }

            if (is_string($value) && trim($value) !== '') {
                return true;
            }

            if (!is_array($value) && filled($value)) {
                return true;
            }
        }

        return false;
    }

    private function normalize(mixed $value): mixed
    {
        if (is_array($value)) {
            $normalized = [];

            foreach ($value as $key => $nestedValue) {
                $normalized[$key] = $this->normalize($nestedValue);
            }

            return $normalized;
        }

        if (is_string($value)) {
            $value = trim($value);

            return $value === '' ? null : $value;
        }

        return $value;
    }
}
