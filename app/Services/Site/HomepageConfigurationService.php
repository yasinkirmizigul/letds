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
        '--home-before-pattern-color' => 'before_pattern_color',
        '--home-after-pattern-color' => 'after_pattern_color',
        '--home-before-text' => 'before_text_color',
        '--home-after-text' => 'after_text_color',
        '--home-hero-before-text' => 'hero_before_text_color',
        '--home-hero-after-text' => 'hero_after_text_color',
        '--home-before-highlight' => 'before_highlight_color',
        '--home-after-highlight' => 'after_highlight_color',
        '--home-before-hotspot' => 'before_hotspot_color',
        '--home-after-hotspot' => 'after_hotspot_color',
        '--home-drag-handle' => 'drag_handle_color',
        '--home-stat-before' => 'cursor_symbol_before_color',
        '--home-stat-after' => 'cursor_symbol_after_color',
        '--home-computer-frame' => 'computer_pv_body_start_color',
        '--home-computer-detail' => 'computer_pv_body_end_color',
        '--home-computer-warm' => 'computer_pv_bar_light_color',
        '--home-computer-neutral' => 'computer_pv_bar_mid_color',
        '--home-computer-cool' => 'computer_pv_bar_vivid_color',
        '--home-computer-alert' => 'computer_pv_bar_dark_color',
        '--home-cta-before-text' => 'cta_before_text_color',
        '--home-cta-after-text' => 'cta_after_text_color',
        '--home-cta-before-hover-bg' => 'cta_before_hover_background',
        '--home-cta-before-hover-text' => 'cta_before_hover_text',
        '--home-cta-after-hover-bg' => 'cta_after_hover_background',
        '--home-cta-after-hover-text' => 'cta_after_hover_text',
    ];

    private const SURFACE_PATTERN_STYLES = [
        'none' => 'none',
        'carbon' => 'repeating-linear-gradient(135deg, color-mix(in srgb, {color} 42%, transparent) 0 2px, transparent 2px 7px), repeating-linear-gradient(45deg, color-mix(in srgb, {color} 22%, transparent) 0 1px, transparent 1px 6px)',
        'micro-grid' => 'linear-gradient(color-mix(in srgb, {color} 44%, transparent) 1px, transparent 1px), linear-gradient(90deg, color-mix(in srgb, {color} 44%, transparent) 1px, transparent 1px)',
        'pixel-grid' => 'conic-gradient(from 90deg at 1px 1px, transparent 25%, color-mix(in srgb, {color} 34%, transparent) 0 50%, transparent 0 75%, color-mix(in srgb, {color} 34%, transparent) 0)',
        'dots' => 'radial-gradient(circle, color-mix(in srgb, {color} 62%, transparent) 1px, transparent 1.5px)',
        'diagonal' => 'repeating-linear-gradient(135deg, transparent 0 8px, color-mix(in srgb, {color} 44%, transparent) 8px 9px)',
        'blueprint' => 'linear-gradient(color-mix(in srgb, {color} 52%, transparent) 1px, transparent 1px), linear-gradient(90deg, color-mix(in srgb, {color} 52%, transparent) 1px, transparent 1px), repeating-linear-gradient(0deg, transparent 0 24%, color-mix(in srgb, {color} 20%, transparent) 25%), repeating-linear-gradient(90deg, transparent 0 24%, color-mix(in srgb, {color} 20%, transparent) 25%)',
        'rings' => 'repeating-radial-gradient(circle at center, transparent 0 18%, color-mix(in srgb, {color} 42%, transparent) 19% 21%, transparent 22% 40%)',
        'grain' => 'radial-gradient(circle at 20% 30%, color-mix(in srgb, {color} 54%, transparent) 0 .7px, transparent 1px), radial-gradient(circle at 72% 64%, color-mix(in srgb, {color} 38%, transparent) 0 .6px, transparent .95px), radial-gradient(circle at 44% 82%, color-mix(in srgb, {color} 28%, transparent) 0 .5px, transparent .9px)',
    ];

    private const SURFACE_GRADIENT_STYLES = [
        'after' => 'radial-gradient(circle at 68% 44%, color-mix(in srgb, var(--home-after-bg) 78%, #2b82ff) 0%, transparent 46%), radial-gradient(circle at 8% 16%, rgba(0, 10, 38, 0.86) 0%, transparent 58%), radial-gradient(circle at 0% 100%, rgba(0, 14, 49, 0.68) 0%, transparent 56%), linear-gradient(128deg, color-mix(in srgb, var(--home-after-bg) 18%, #011337), color-mix(in srgb, var(--home-after-bg) 82%, #0b56c4))',
        'before' => 'radial-gradient(circle at 78% 28%, color-mix(in srgb, var(--home-before-highlight) 16%, transparent) 0%, transparent 38%), linear-gradient(135deg, color-mix(in srgb, var(--home-before-bg) 92%, #ffffff), color-mix(in srgb, var(--home-before-bg) 82%, var(--home-before-highlight)))',
    ];

    public function __construct(
        private readonly SiteTranslationSyncService $translationSyncService,
        private readonly HomepageSectionService $homepageSectionService,
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
        $groupFields = collect($this->settingGroups())
            ->flatMap(fn (array $group) => $this->expandSettingFields($group['fields'] ?? []))
            ->values();
        $contentColorFields = collect($this->contentFields())
            ->flatMap(fn (array $field) => $field['colors'] ?? [])
            ->map(fn (array $field) => array_replace(['type' => 'color'], $field));

        return $groupFields
            ->concat($contentColorFields)
            ->filter(fn (array $field) => filled($field['key'] ?? null))
            ->unique('key')
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

    public function defaultBackgrounds(): array
    {
        return collect($this->schema()['default_backgrounds'] ?? [])
            ->mapWithKeys(function ($path, string $theme): array {
                $path = ltrim(trim((string) $path), '/');

                $absolutePath = public_path($path);
                $version = is_file($absolutePath) ? filemtime($absolutePath) : null;

                return $path === '' ? [] : [
                    $theme => [
                        'path' => $path,
                        'url' => asset($path) . ($version ? '?v=' . $version : ''),
                    ],
                ];
            })
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
                'title_color' => (string) ($settings["tooltip_{$index}_title_color"] ?? '#ffffff'),
                'highlighted_title_color' => (string) ($settings["tooltip_{$index}_highlighted_title_color"] ?? '#445963'),
                'aria_label' => trim(strip_tags((string) ($content["tooltip_{$index}_title"] ?? ''))),
                'position' => $index,
            ])
            ->values()
            ->all();

        $modes = $this->resolvedModes($content, $settings);
        $headerLogo = $this->headerLogo($settings);
        $backgroundImage = $this->mediaAsset($settings, 'background_media_id');
        $backgroundDefaults = $this->defaultBackgrounds();
        $sections = $this->homepageSectionService->resolved($locale);

        return compact('content', 'settings', 'tooltips', 'modes', 'headerLogo', 'backgroundImage', 'backgroundDefaults', 'sections');
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
                $styles['--home-computer-gradient-end'] = ($settings[$prefix.'computer_pv_fill_mode'] ?? 'gradient') === 'gradient'
                    ? (string) ($settings[$prefix.'computer_pv_body_end_color'] ?? '#0060ea')
                    : (string) ($settings[$prefix.'computer_pv_body_start_color'] ?? '#072247');
                $styles = array_replace($styles, $this->resolvedSurfaceStyles($settings, $prefix));

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

    private function resolvedSurfaceStyles(array $settings, string $prefix): array
    {
        $styles = [];

        foreach (['before', 'after'] as $side) {
            $keyPrefix = $prefix.$side;
            $pattern = (string) ($settings[$keyPrefix.'_pattern'] ?? 'none');
            $colorEffect = (string) ($settings[$keyPrefix.'_color_effect'] ?? ($side === 'after' ? 'gradient' : 'solid'));
            $patternTemplate = self::SURFACE_PATTERN_STYLES[$pattern] ?? self::SURFACE_PATTERN_STYLES['none'];
            $patternColorProperty = "--home-{$side}-pattern-color";

            $styles["--home-{$side}-color-layer"] = $colorEffect === 'gradient'
                ? self::SURFACE_GRADIENT_STYLES[$side]
                : "var(--home-{$side}-bg)";
            $styles["--home-{$side}-surface-opacity"] = $colorEffect === 'solid'
                ? '1'
                : 'var(--home-background-overlay-opacity)';
            $styles["--home-{$side}-pattern-image"] = str_replace('{color}', "var({$patternColorProperty})", $patternTemplate);
            $styles["--home-{$side}-pattern-opacity"] = $this->cssNumber((float) ($settings[$keyPrefix.'_pattern_opacity'] ?? 0) / 100);
            $styles["--home-{$side}-pattern-size"] = $this->cssNumber((float) ($settings[$keyPrefix.'_pattern_scale'] ?? 28)).'px';
            $styles["--home-{$side}-pattern-blur"] = $this->cssNumber((float) ($settings[$keyPrefix.'_pattern_blur'] ?? 0)).'px';
            $styles["--home-{$side}-pattern-blend"] = (string) ($settings[$keyPrefix.'_pattern_blend'] ?? 'soft-light');
        }

        return $styles;
    }

    private function expandSettingFields(array $fields): array
    {
        return collect($fields)
            ->flatMap(function (array $field): array {
                $nested = $field['fields'] ?? [];

                return is_array($nested) && $nested !== []
                    ? $this->expandSettingFields($nested)
                    : [$field];
            })
            ->values()
            ->all();
    }

    private function cssNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
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
