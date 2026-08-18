<?php

namespace App\Services\Site;

use App\Models\Site\SiteHomepageSection;

class HomepageSectionService
{
    public const ICON_OPTIONS = [
        'heart' => 'Müşteri memnuniyeti',
        'adjustments' => 'Esneklik ve ayarlar',
        'chart' => 'Analiz ve büyüme',
        'shield' => 'Güven ve koruma',
        'clock' => 'Zaman ve hız',
        'sparkles' => 'Kalite ve yenilik',
        'blueprint' => 'Araştırma ve planlama',
        'document' => 'Makale ve doküman',
        'report' => 'Raporlama',
        'health' => 'Klinik araştırma',
        'ai' => 'Yapay zeka',
        'conversation' => 'Ön görüşme',
        'search' => 'Değerlendirme',
        'support' => 'Destek ve revizyon',
    ];

    public const TYPE_OPTIONS = [
        'features' => 'Özellik kartları',
        'services' => 'Hizmet kartları',
        'process' => 'Süreç adımları',
    ];

    public const PLACEMENT_OPTIONS = [
        'homepage' => 'Ana sayfa',
        'services' => 'Hizmetler sayfası',
    ];

    public const SURFACE_OPTIONS = [
        'light' => 'Açık',
        'tint' => 'Yumuşak renkli',
        'dark' => 'Koyu',
    ];

    public const ALIGNMENT_OPTIONS = [
        'left' => 'Sola hizalı',
        'center' => 'Ortalanmış',
    ];

    public function resolved(?string $locale = null): array
    {
        $locale = trim((string) ($locale ?: app()->getLocale()));

        return SiteHomepageSection::query()
            ->active()
            ->forPlacement('homepage')
            ->where('type', 'features')
            ->with([
                'translations',
                'items' => fn ($query) => $query->active(),
                'items.translations',
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (SiteHomepageSection $section) => $this->resolveSection($section, $locale))
            ->filter(fn (array $section) => $section['items'] !== [])
            ->values()
            ->all();
    }

    public function resolvedForPlacement(string $placement, ?string $locale = null): array
    {
        $locale = trim((string) ($locale ?: app()->getLocale()));

        if (! array_key_exists($placement, self::PLACEMENT_OPTIONS)) {
            return [];
        }

        return SiteHomepageSection::query()
            ->active()
            ->forPlacement($placement)
            ->with([
                'translations',
                'items' => fn ($query) => $query->active(),
                'items.translations',
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (SiteHomepageSection $section) => $this->resolveSection($section, $locale))
            ->filter(fn (array $section) => $section['items'] !== [])
            ->values()
            ->all();
    }

    public function safeLink(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, '/') || str_starts_with($value, '#') || str_starts_with($value, '?')) {
            return $value;
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https', 'mailto', 'tel'], true) ? $value : null;
    }

    private function resolveSection(SiteHomepageSection $section, string $locale): array
    {
        $settings = $section->settings ?? [];
        $columns = min(6, max(2, (int) ($settings['columns'] ?? 3)));
        $alignment = array_key_exists((string) ($settings['alignment'] ?? ''), self::ALIGNMENT_OPTIONS)
            ? (string) $settings['alignment']
            : 'left';
        $surface = array_key_exists((string) ($settings['surface'] ?? ''), self::SURFACE_OPTIONS)
            ? (string) $settings['surface']
            : 'tint';
        $accentColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($settings['accent_color'] ?? ''))
            ? strtolower((string) $settings['accent_color'])
            : '#ec6367';

        return [
            'id' => $section->id,
            'type' => array_key_exists($section->type, self::TYPE_OPTIONS) ? $section->type : 'features',
            'placement' => $section->placement ?: 'homepage',
            'eyebrow' => (string) $section->localized('eyebrow', $locale, ''),
            'title' => (string) $section->localized('title', $locale, ''),
            'description' => (string) $section->localized('description', $locale, ''),
            'columns' => $columns,
            'alignment' => $alignment,
            'surface' => $surface,
            'accent_color' => $accentColor,
            'items' => $section->items
                ->map(fn ($item) => [
                    'id' => $item->id,
                    'title' => (string) $item->localized('title', $locale, ''),
                    'description' => (string) $item->localized('description', $locale, ''),
                    'icon' => array_key_exists($item->icon, self::ICON_OPTIONS) ? $item->icon : 'sparkles',
                    'link_label' => (string) $item->localized('link_label', $locale, ''),
                    'link_url' => $this->safeLink($item->link_url),
                ])
                ->values()
                ->all(),
        ];
    }
}
