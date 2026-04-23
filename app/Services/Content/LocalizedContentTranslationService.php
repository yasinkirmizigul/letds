<?php

namespace App\Services\Content;

use App\Models\Site\SiteLanguage;
use App\Support\Security\HtmlSanitizer;
use App\Support\Site\SiteLocalization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LocalizedContentTranslationService
{
    public function sync(
        Model $model,
        string $relationName,
        array $translations,
        array $fields,
        ?array $slugConfig = null
    ): void {
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

                $payload[$field] = $this->normalize($field, $values[$field]);
            }

            if (!$this->hasMeaningfulContent($payload)) {
                $relation->where('locale', $locale)->delete();

                continue;
            }

            if ($slugConfig && in_array('slug', $fields, true)) {
                $payload['slug'] = $this->resolveTranslationSlug($payload, $slugConfig, $model, $locale);
            }

            $relation->updateOrCreate(
                ['locale' => $locale],
                array_merge($payload, ['locale' => $locale])
            );
        }
    }

    public function uniqueSlug(string $slug, array $config, ?int $ignoreId = null, ?string $ignoreLocale = null): string
    {
        $base = Str::slug($slug) ?: ($config['fallback'] ?? 'icerik');
        $candidate = $base;
        $suffix = 2;

        $baseModel = $config['base_model'];
        $translationModel = $config['translation_model'];
        $foreignKey = $config['foreign_key'];

        while (
            $baseModel::query()
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->where('slug', $candidate)
                ->exists()
            || $translationModel::query()
                ->where('slug', $candidate)
                ->when($ignoreId && $ignoreLocale, function ($query) use ($foreignKey, $ignoreId, $ignoreLocale) {
                    $query->where(function ($nested) use ($foreignKey, $ignoreId, $ignoreLocale) {
                        $nested
                            ->where($foreignKey, '!=', $ignoreId)
                            ->orWhere('locale', '!=', $ignoreLocale);
                    });
                })
                ->exists()
        ) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
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

    private function resolveTranslationSlug(array $payload, array $config, Model $model, string $locale): ?string
    {
        $source = $payload['slug']
            ?? $payload['title']
            ?? $payload['name']
            ?? null;

        if (!filled($source)) {
            return null;
        }

        return $this->uniqueSlug((string) $source, $config, (int) $model->getKey(), $locale);
    }

    private function normalize(string $field, mixed $value): mixed
    {
        if (is_array($value)) {
            $normalized = [];

            foreach ($value as $key => $nestedValue) {
                $normalized[$key] = $this->normalize((string) $key, $nestedValue);
            }

            return $normalized;
        }

        if (is_string($value)) {
            if ($field === 'content') {
                return HtmlSanitizer::sanitize($value);
            }

            $value = trim($value);

            return $value === '' ? null : $value;
        }

        return $value;
    }
}
