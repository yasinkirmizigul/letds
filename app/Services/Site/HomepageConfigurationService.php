<?php

namespace App\Services\Site;

use App\Models\Admin\Media\Media;
use App\Models\Site\SiteHomepageConfig;
use App\Models\Site\SiteLanguage;
use App\Support\Security\HtmlSanitizer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class HomepageConfigurationService
{
    private const MODE_STYLE_SETTINGS = [
        '--home-before-bg' => 'before_background_color',
        '--home-after-bg' => 'after_background_color',
        '--home-before-text' => 'before_text_color',
        '--home-after-text' => 'after_text_color',
        '--home-before-highlight' => 'before_highlight_color',
        '--home-after-highlight' => 'after_highlight_color',
        '--home-before-hotspot' => 'before_hotspot_color',
        '--home-after-hotspot' => 'after_hotspot_color',
        '--home-drag-handle' => 'drag_handle_color',
        '--home-stat-before' => 'cursor_symbol_before_color',
        '--home-stat-after' => 'cursor_symbol_after_color',
        '--home-cta-before-text' => 'cta_before_text_color',
        '--home-cta-after-text' => 'cta_after_text_color',
        '--home-cta-before-hover-bg' => 'cta_before_hover_background',
        '--home-cta-before-hover-text' => 'cta_before_hover_text',
        '--home-cta-after-hover-bg' => 'cta_after_hover_background',
        '--home-cta-after-hover-text' => 'cta_after_hover_text',
    ];

    public function __construct(
        private readonly SiteTranslationSyncService $translationSyncService,
    ) {}

    public function schema(): array
    {
        return config('site_homepage', []);
    }

    public function contentFields(): array
    {
        return collect($this->schema()['content_fields'] ?? [])
            ->filter(fn (array $field) => filled($field['key'] ?? null))
            ->values()
            ->all();
    }

    public function settingGroups(): array
    {
        return $this->schema()['setting_groups'] ?? [];
    }

    public function settingFields(): array
    {
        return collect($this->settingGroups())
            ->flatMap(fn (array $group) => $group['fields'] ?? [])
            ->filter(fn (array $field) => filled($field['key'] ?? null))
            ->values()
            ->all();
    }

    public function contentDefaults(): array
    {
        return collect($this->contentFields())
            ->mapWithKeys(fn (array $field) => [$field['key'] => $field['default'] ?? null])
            ->all();
    }

    public function settingDefaults(): array
    {
        return collect($this->settingFields())
            ->mapWithKeys(fn (array $field) => [$field['key'] => $field['default'] ?? null])
            ->all();
    }

    public function current(): SiteHomepageConfig
    {
        return SiteHomepageConfig::query()->firstOrCreate(
            ['key' => (string) ($this->schema()['key'] ?? 'homepage')],
            [
                'content' => $this->contentDefaults(),
                'settings' => $this->settingDefaults(),
            ]
        );
    }

    public function validationRules(): array
    {
        $rules = [
            'translations' => ['nullable', 'array'],
            'settings' => ['required', 'array'],
        ];

        foreach ($this->contentFields() as $field) {
            $key = (string) $field['key'];
            $fieldRules = $field['rules'] ?? ['nullable', 'string'];
            $rules[$key] = $fieldRules;
            $rules['translations.*.'.$key] = $this->optionalRules($fieldRules);
        }

        foreach ($this->settingFields() as $field) {
            $key = (string) $field['key'];
            $rules['settings.'.$key] = match ($field['type'] ?? 'text') {
                'boolean' => ['required', 'boolean'],
                'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'select' => ['required', 'string', Rule::in(array_keys($field['options'] ?? []))],
                'range' => [
                    'required',
                    'numeric',
                    'min:'.(float) ($field['min'] ?? 0),
                    'max:'.(float) ($field['max'] ?? 100),
                ],
                'media' => [
                    'nullable',
                    'integer',
                    Rule::exists('media', 'id')->where(fn ($query) => $query
                        ->where('mime_type', 'like', 'image/%')
                        ->whereNull('deleted_at')),
                ],
                default => $field['rules'] ?? ['nullable', 'string'],
            };
        }

        return $rules;
    }

    public function persist(array $validated): SiteHomepageConfig
    {
        return DB::transaction(function () use ($validated): SiteHomepageConfig {
            $config = $this->current();
            $content = $this->normalizeContent(Arr::only($validated, array_keys($this->contentDefaults())));
            $settings = $this->normalizeSettings($validated['settings'] ?? []);

            $config->update([
                'content' => array_replace($this->contentDefaults(), $content),
                'settings' => array_replace($this->settingDefaults(), $settings),
            ]);

            $translations = collect($validated['translations'] ?? [])
                ->map(fn ($values) => [
                    'content' => $this->normalizeContent(is_array($values) ? $values : []),
                ])
                ->all();

            $this->translationSyncService->sync(
                $config,
                'translations',
                $translations,
                ['content']
            );

            return $config->fresh('translations');
        });
    }

    public function resolved(?string $locale = null): array
    {
        $config = $this->current()->loadMissing('translations');
        $content = array_replace($this->contentDefaults(), $config->content ?? []);
        $settings = $this->resolvedSettings($config->settings ?? []);
        $locale = trim((string) ($locale ?: app()->getLocale()));
        $defaultLocale = (string) (SiteLanguage::query()->where('is_default', true)->value('code') ?: config('app.locale'));

        if ($locale !== '' && $locale !== $defaultLocale) {
            $translation = $config->translations->firstWhere('locale', $locale);

            foreach ($translation?->content ?? [] as $key => $value) {
                if (filled($value)) {
                    $content[$key] = $value;
                }
            }
        }

        $tooltips = collect(range(1, 4))
            ->filter(fn (int $index) => (bool) ($settings["tooltip_{$index}_enabled"] ?? true))
            ->map(fn (int $index) => [
                'key' => "pos-item-{$index}",
                'title' => (string) ($content["tooltip_{$index}_title"] ?? ''),
                'highlighted_title' => (string) ($content["tooltip_{$index}_highlighted_title"] ?? ''),
                'aria_label' => trim(strip_tags((string) ($content["tooltip_{$index}_title"] ?? ''))),
                'position' => $index,
            ])
            ->values()
            ->all();

        $modes = $this->resolvedModes($content, $settings);
        $headerLogo = $this->headerLogo($settings);
        $backgroundImage = $this->mediaAsset($settings, 'background_media_id');

        return compact('content', 'settings', 'tooltips', 'modes', 'headerLogo', 'backgroundImage');
    }

    public function safeLink(?string $value, string $fallback): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return $fallback;
        }

        if (str_starts_with($value, '/') || str_starts_with($value, '#') || str_starts_with($value, '?')) {
            return $value;
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https', 'mailto', 'tel'], true) ? $value : $fallback;
    }

    public function hasSafeLinks(array $payload): bool
    {
        return $this->unsafeLinks($payload) === [];
    }

    public function unsafeLinks(array $payload): array
    {
        $urlKeys = collect($this->contentFields())
            ->filter(fn (array $field) => ($field['type'] ?? null) === 'url')
            ->pluck('key')
            ->filter()
            ->values();
        $unsafe = [];

        foreach ($urlKeys as $key) {
            $value = (string) ($payload[$key] ?? '');

            if ($value !== '' && $this->safeLink($value, '') === '') {
                $unsafe[] = (string) $key;
            }
        }

        foreach ($payload['translations'] ?? [] as $locale => $translation) {
            if (! is_array($translation)) {
                continue;
            }

            foreach ($urlKeys as $key) {
                $value = (string) ($translation[$key] ?? '');

                if ($value !== '' && $this->safeLink($value, '') === '') {
                    $unsafe[] = 'translations.'.(string) $locale.'.'.(string) $key;
                }
            }
        }

        return array_values(array_unique($unsafe));
    }

    public function headerLogo(array $settings): ?array
    {
        return $this->mediaAsset($settings, 'header_logo_media_id');
    }

    public function mediaAsset(array $settings, string $key): ?array
    {
        $mediaId = (int) ($settings[$key] ?? 0);

        if ($mediaId < 1) {
            return null;
        }

        $media = Media::query()
            ->where('mime_type', 'like', 'image/%')
            ->find($mediaId);

        if (! $media) {
            return null;
        }

        return [
            'id' => $media->id,
            'url' => $media->url(),
            'alt' => trim((string) ($media->alt ?: $media->title ?: $media->original_name)),
        ];
    }

    private function normalizeContent(array $values): array
    {
        $fields = collect($this->contentFields())->keyBy('key');
        $normalized = [];

        foreach ($values as $key => $value) {
            if (! $fields->has($key)) {
                continue;
            }

            $value = is_string($value) ? trim($value) : $value;
            $normalized[$key] = ($fields->get($key)['sanitize'] ?? null) === 'html'
                ? HtmlSanitizer::sanitize((string) $value)
                : ($value === '' ? null : $value);
        }

        return $normalized;
    }

    private function normalizeSettings(array $values): array
    {
        $fields = collect($this->settingFields())->keyBy('key');
        $normalized = [];

        foreach ($values as $key => $value) {
            $field = $fields->get($key);

            if (! $field) {
                continue;
            }

            $normalized[$key] = match ($field['type'] ?? 'text') {
                'boolean' => (bool) $value,
                'color' => strtolower(trim((string) $value)),
                'media' => filled($value) ? (int) $value : null,
                'range' => (float) $value,
                default => is_string($value) ? trim($value) : $value,
            };
        }

        return $normalized;
    }

    private function resolvedSettings(array $values): array
    {
        $defaults = $this->settingDefaults();
        $settings = array_replace($defaults, $values);

        foreach ($this->settingFields() as $field) {
            $key = (string) $field['key'];

            if (($field['type'] ?? null) === 'color' && ! preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($settings[$key] ?? ''))) {
                $settings[$key] = $defaults[$key] ?? '#000000';
            }

            if (($field['type'] ?? null) === 'boolean') {
                $settings[$key] = (bool) ($settings[$key] ?? false);
            }

            if (($field['type'] ?? null) === 'select' && ! array_key_exists((string) ($settings[$key] ?? ''), $field['options'] ?? [])) {
                $settings[$key] = $defaults[$key] ?? null;
            }

            if (($field['type'] ?? null) === 'range') {
                $min = (float) ($field['min'] ?? 0);
                $max = (float) ($field['max'] ?? 100);
                $value = is_numeric($settings[$key] ?? null) ? (float) $settings[$key] : (float) ($defaults[$key] ?? $min);
                $settings[$key] = min($max, max($min, $value));
            }
        }

        return $settings;
    }

    private function resolvedModes(array $content, array $settings): array
    {
        return collect($this->schema()['modes'] ?? [])
            ->mapWithKeys(function (array $definition, string $key) use ($content, $settings): array {
                $prefix = (string) ($definition['settings_prefix'] ?? '');
                $styles = collect(self::MODE_STYLE_SETTINGS)
                    ->mapWithKeys(fn (string $settingKey, string $property) => [
                        $property => (string) ($settings[$prefix.$settingKey] ?? ''),
                    ])
                    ->all();

                return [$key => [
                    'key' => $key,
                    'label' => (string) ($content[$definition['label_key']] ?? $definition['label'] ?? $key),
                    'icon' => in_array(($definition['icon'] ?? null), ['chart', 'message'], true)
                        ? $definition['icon']
                        : 'chart',
                    'hero_title' => (string) ($content[$definition['hero_title_key']] ?? ''),
                    'cta_label' => (string) ($content[$definition['cta_label_key']] ?? ''),
                    'cta_url' => (string) ($content[$definition['cta_url_key']] ?? ''),
                    'styles' => $styles,
                ]];
            })
            ->all();
    }

    private function optionalRules(array $rules): array
    {
        return collect($rules)
            ->reject(fn ($rule) => $rule === 'required')
            ->prepend('nullable')
            ->unique()
            ->values()
            ->all();
    }
}
