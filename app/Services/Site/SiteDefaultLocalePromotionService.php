<?php

namespace App\Services\Site;

use App\Models\Site\HomeSlider;
use App\Models\Site\SiteCounter;
use App\Models\Site\SiteFaq;
use App\Models\Site\SiteLanguage;
use App\Models\Site\SiteNavigationItem;
use App\Models\Site\SitePage;
use App\Models\Site\SitePageTranslation;
use App\Models\Site\SiteSetting;
use App\Support\Site\SiteLocalization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SiteDefaultLocalePromotionService
{
    private const MAP = [
        [
            'model' => SitePage::class,
            'relation' => 'translations',
            'fields' => [
                'title',
                'slug',
                'hero_kicker',
                'excerpt',
                'content',
                'meta_title',
                'meta_description',
                'meta_keywords',
            ],
        ],
        [
            'model' => SiteFaq::class,
            'relation' => 'translations',
            'fields' => ['group_label', 'question', 'answer'],
        ],
        [
            'model' => SiteCounter::class,
            'relation' => 'translations',
            'fields' => ['label', 'prefix', 'suffix', 'description'],
        ],
        [
            'model' => SiteNavigationItem::class,
            'relation' => 'translations',
            'fields' => ['title'],
        ],
        [
            'model' => SiteSetting::class,
            'relation' => 'translations',
            'fields' => [
                'site_name',
                'site_tagline',
                'hero_notice',
                'address_line',
                'map_title',
                'office_hours',
                'footer_note',
                'under_construction_title',
                'under_construction_message',
                'ui_lines',
            ],
        ],
        [
            'model' => HomeSlider::class,
            'relation' => 'translations',
            'fields' => ['badge', 'title', 'subtitle', 'body', 'cta_label', 'cta_url'],
        ],
    ];

    public function __construct(
        private readonly SiteTranslationSyncService $translationSyncService,
    ) {}

    public function promote(SiteLanguage $targetLanguage): void
    {
        $targetLocale = $targetLanguage->code;
        $currentDefaultLocale = SiteLocalization::defaultLocale();

        if ($targetLocale === $currentDefaultLocale) {
            return;
        }

        DB::transaction(function () use ($targetLanguage, $targetLocale, $currentDefaultLocale) {
            foreach (self::MAP as $config) {
                $this->promoteModel(
                    $config['model'],
                    $config['relation'],
                    $config['fields'],
                    $currentDefaultLocale,
                    $targetLocale,
                );
            }

            SiteLanguage::query()->update(['is_default' => false]);

            SiteLanguage::query()
                ->whereKey($targetLanguage->id)
                ->update([
                    'is_default' => true,
                    'is_active' => true,
                ]);
        });
    }

    private function promoteModel(
        string $modelClass,
        string $relationName,
        array $fields,
        string $fromLocale,
        string $toLocale
    ): void {
        $modelClass::query()
            ->with([
                $relationName => fn ($query) => $query->whereIn('locale', [$fromLocale, $toLocale]),
            ])
            ->chunkById(100, function ($models) use ($relationName, $fields, $fromLocale, $toLocale) {
                foreach ($models as $model) {
                    $this->promoteRecord($model, $relationName, $fields, $fromLocale, $toLocale);
                }
            });
    }

    private function promoteRecord(
        Model $model,
        string $relationName,
        array $fields,
        string $fromLocale,
        string $toLocale
    ): void {
        $basePayload = $this->extractFields($model, $fields);
        $targetTranslation = $model->{$relationName}->firstWhere('locale', $toLocale);
        $promotedPayload = [];

        foreach ($fields as $field) {
            $translatedValue = $targetTranslation?->{$field};
            $promotedPayload[$field] = $this->valueOrFallback($translatedValue, $basePayload[$field] ?? null);
        }

        if ($model instanceof SitePage && filled($promotedPayload['slug'] ?? null)) {
            $promotedPayload['slug'] = $this->uniquePageSlug((string) $promotedPayload['slug'], $model->id, $toLocale);
        }

        $model->forceFill($promotedPayload)->save();

        if ($model instanceof SitePage && filled($basePayload['slug'] ?? null)) {
            $basePayload['slug'] = $this->uniquePageTranslationSlug((string) $basePayload['slug'], $model->id, $fromLocale);
        }

        if ($this->translationSyncService->hasMeaningfulContent($basePayload)) {
            $model->{$relationName}()->updateOrCreate(
                ['locale' => $fromLocale],
                array_merge($basePayload, ['locale' => $fromLocale])
            );
        } else {
            $model->{$relationName}()->where('locale', $fromLocale)->delete();
        }

        $model->{$relationName}()->where('locale', $toLocale)->delete();
    }

    private function extractFields(Model $model, array $fields): array
    {
        $payload = [];

        foreach ($fields as $field) {
            $payload[$field] = $model->getAttribute($field);
        }

        return $payload;
    }

    private function valueOrFallback(mixed $value, mixed $fallback): mixed
    {
        if (is_array($value)) {
            return $this->translationSyncService->hasMeaningfulContent($value) ? $value : $fallback;
        }

        if (is_string($value)) {
            $value = trim($value);

            return $value !== '' ? $value : $fallback;
        }

        return filled($value) ? $value : $fallback;
    }

    private function uniquePageSlug(string $slug, int $ignoreId, string $ignoreLocale): string
    {
        $base = Str::slug($slug) ?: 'sayfa';
        $candidate = $base;
        $suffix = 2;

        while (
            SitePage::query()
                ->whereKeyNot($ignoreId)
                ->where('slug', $candidate)
                ->exists()
            || SitePageTranslation::query()
                ->where('slug', $candidate)
                ->where(function ($query) use ($ignoreId, $ignoreLocale) {
                    $query
                        ->where('site_page_id', '!=', $ignoreId)
                        ->orWhere('locale', '!=', $ignoreLocale);
                })
                ->exists()
        ) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function uniquePageTranslationSlug(string $slug, int $ignoreId, string $ignoreLocale): string
    {
        $base = Str::slug($slug) ?: 'sayfa';
        $candidate = $base;
        $suffix = 2;

        while (
            SitePage::query()->where('slug', $candidate)->exists()
            || SitePageTranslation::query()
                ->where('slug', $candidate)
                ->where(function ($query) use ($ignoreId, $ignoreLocale) {
                    $query
                        ->where('site_page_id', '!=', $ignoreId)
                        ->orWhere('locale', '!=', $ignoreLocale);
                })
                ->exists()
        ) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }
}
