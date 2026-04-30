<?php

namespace App\Services\Site;

use App\Models\Site\SiteLanguage;
use App\Models\Site\SitePage;
use App\Models\Site\SiteSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class SeoFileGenerator
{
    public function generate(SiteSetting $settings): array
    {
        $entries = $this->sitemapEntries($settings);
        $contents = $this->editableContents($settings, $entries);

        File::put(public_path('sitemap.xml'), $contents['sitemap_xml_content']);
        File::put(public_path('robots.txt'), $contents['robots_txt_content']);
        File::put(public_path('llms.txt'), $contents['llms_txt_content']);

        $settings->forceFill(['seo_files_generated_at' => now()])->save();

        return [
            'entries' => count($entries),
            'files' => $this->status(),
        ];
    }

    public function status(): array
    {
        return collect(['sitemap.xml', 'robots.txt', 'llms.txt'])
            ->mapWithKeys(function (string $file): array {
                $path = public_path($file);
                $exists = File::exists($path);

                return [
                    $file => [
                        'name' => $file,
                        'path' => $path,
                        'url' => url($file),
                        'exists' => $exists,
                        'size' => $exists ? File::size($path) : null,
                        'modified_at' => $exists ? Carbon::createFromTimestamp(File::lastModified($path)) : null,
                    ],
                ];
            })
            ->all();
    }

    public function automaticContents(SiteSetting $settings): array
    {
        $entries = $this->sitemapEntries($settings);

        return [
            'entries' => count($entries),
            'sitemap_xml_content' => $this->buildSitemapXml($entries),
            'robots_txt_content' => $this->buildDefaultRobotsTxt($settings),
            'llms_txt_content' => $this->buildDefaultLlmsTxt($settings, $entries),
        ];
    }

    public function sitemapEntries(SiteSetting $settings): array
    {
        $baseUrl = $this->baseUrl($settings);
        $languages = $this->activeLanguages();
        $defaultLocale = $this->defaultLocale($languages);
        $entries = [];

        if ($this->enabled($settings, 'sitemap_include_home')) {
            $this->addEntry($entries, $this->absoluteUrl('/', $baseUrl), now(), 'daily', 1.0, 'Ana Sayfa');

            foreach ($languages as $language) {
                $code = (string) $language->code;

                if ($code !== $defaultLocale) {
                    $this->addEntry($entries, $this->absoluteUrl('/' . $code, $baseUrl), now(), 'daily', 0.9, strtoupper($code) . ' Ana Sayfa');
                }
            }
        }

        if ($this->enabled($settings, 'sitemap_include_contact')) {
            $this->addEntry($entries, $this->absoluteUrl('/iletisim', $baseUrl), now(), 'monthly', 0.6, 'Iletisim');
        }

        if ($this->enabled($settings, 'sitemap_include_member_pages')) {
            $this->addEntry($entries, $this->absoluteUrl('/uyelik-bilgilendirmesi', $baseUrl), now(), 'monthly', 0.5, 'Uyelik Bilgilendirmesi');
        }

        if ($this->enabled($settings, 'sitemap_include_pages')) {
            SitePage::query()
                ->publishedVisible()
                ->with('translations')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->each(function (SitePage $page) use (&$entries, $languages, $defaultLocale, $baseUrl): void {
                    foreach ($languages as $language) {
                        $locale = (string) $language->code;
                        $slug = trim($page->slugForLocale($locale), '/');

                        if ($slug === '') {
                            continue;
                        }

                        $path = $locale === $defaultLocale
                            ? '/' . $slug
                            : '/' . $locale . '/' . $slug;

                        $this->addEntry(
                            $entries,
                            $this->absoluteUrl($path, $baseUrl),
                            $page->updated_at,
                            'weekly',
                            0.7,
                            (string) $page->localized('title', $locale, $page->title)
                        );
                    }
                });
        }

        foreach ($this->extraUrls((string) $settings->sitemap_extra_urls, $baseUrl) as $url) {
            $this->addEntry($entries, $url, now(), 'monthly', 0.5, $url);
        }

        return array_values($entries);
    }

    private function buildSitemapXml(array $entries): string
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($entries as $entry) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>' . $this->xml($entry['loc']) . '</loc>';

            if (filled($entry['lastmod'] ?? null)) {
                $lines[] = '    <lastmod>' . $this->xml((string) $entry['lastmod']) . '</lastmod>';
            }

            if (filled($entry['changefreq'] ?? null)) {
                $lines[] = '    <changefreq>' . $this->xml((string) $entry['changefreq']) . '</changefreq>';
            }

            if (isset($entry['priority'])) {
                $lines[] = '    <priority>' . number_format((float) $entry['priority'], 1, '.', '') . '</priority>';
            }

            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    private function editableContents(SiteSetting $settings, array $entries): array
    {
        $sitemapContent = trim((string) $settings->sitemap_xml_content);

        return [
            'sitemap_xml_content' => $sitemapContent !== ''
                ? rtrim($this->renderPlaceholders($sitemapContent, $settings)) . PHP_EOL
                : $this->buildSitemapXml($entries),
            'robots_txt_content' => $this->buildRobotsTxt($settings),
            'llms_txt_content' => $this->buildLlmsTxt($settings, $entries),
        ];
    }

    private function buildRobotsTxt(SiteSetting $settings): string
    {
        $content = trim((string) $settings->robots_txt_content);

        if ($content === '') {
            return $this->buildDefaultRobotsTxt($settings);
        }

        $content = $this->renderPlaceholders($content, $settings);

        if (!preg_match('/^sitemap:\s*/im', $content)) {
            $content .= PHP_EOL . PHP_EOL . 'Sitemap: ' . $this->baseUrl($settings) . '/sitemap.xml';
        }

        return rtrim($content) . PHP_EOL;
    }

    private function buildDefaultRobotsTxt(SiteSetting $settings): string
    {
        return $this->renderPlaceholders(implode(PHP_EOL, [
            'User-agent: *',
            'Allow: /',
            '',
            'Sitemap: {sitemap_url}',
        ]), $settings) . PHP_EOL;
    }

    private function buildLlmsTxt(SiteSetting $settings, array $entries): string
    {
        $content = trim((string) $settings->llms_txt_content);

        if ($content !== '') {
            return rtrim($this->renderPlaceholders($content, $settings)) . PHP_EOL;
        }

        return $this->buildDefaultLlmsTxt($settings, $entries);
    }

    private function buildDefaultLlmsTxt(SiteSetting $settings, array $entries): string
    {
        $siteName = $settings->site_name ?: config('app.name');
        $tagline = $settings->site_tagline ?: 'Site content and public pages.';
        $baseUrl = $this->baseUrl($settings);
        $lines = [
            '# ' . $siteName,
            '',
            '> ' . $tagline,
            '',
            '## Site',
            '- Home: ' . $baseUrl,
            '- Sitemap: ' . $baseUrl . '/sitemap.xml',
            '- Robots: ' . $baseUrl . '/robots.txt',
            '',
            '## Published URLs',
        ];

        foreach ($entries as $entry) {
            $title = trim((string) ($entry['title'] ?? 'URL'));
            $lines[] = '- ' . ($title !== '' ? $title : 'URL') . ': ' . $entry['loc'];
        }

        $lines[] = '';
        $lines[] = '## Notes';
        $lines[] = 'This file is generated from the admin site settings panel.';

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    private function addEntry(
        array &$entries,
        string $loc,
        mixed $lastmod = null,
        string $changefreq = 'weekly',
        float $priority = 0.5,
        ?string $title = null
    ): void {
        $loc = trim($loc);

        if ($loc === '') {
            return;
        }

        $entries[$loc] = [
            'loc' => $loc,
            'lastmod' => $this->formatLastmod($lastmod),
            'changefreq' => $changefreq,
            'priority' => $priority,
            'title' => $title,
        ];
    }

    private function activeLanguages(): Collection
    {
        $languages = SiteLanguage::query()
            ->active()
            ->ordered()
            ->get(['code', 'is_default']);

        if ($languages->isNotEmpty()) {
            return $languages;
        }

        return collect([(object) [
            'code' => app()->getLocale(),
            'is_default' => true,
        ]]);
    }

    private function defaultLocale(Collection $languages): string
    {
        $default = $languages->firstWhere('is_default', true);

        return (string) ($default->code ?? $languages->first()?->code ?? app()->getLocale());
    }

    private function extraUrls(string $source, string $baseUrl): array
    {
        return collect(preg_split('/\R/u', $source) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->reject(fn (string $line): bool => $line === '' || str_starts_with($line, '#'))
            ->map(fn (string $line): string => $this->absoluteUrl($line, $baseUrl))
            ->unique()
            ->values()
            ->all();
    }

    private function enabled(SiteSetting $settings, string $attribute): bool
    {
        $value = $settings->{$attribute};

        return $value === null ? true : (bool) $value;
    }

    private function baseUrl(SiteSetting $settings): string
    {
        $baseUrl = trim((string) $settings->seo_base_url);

        if ($baseUrl === '') {
            $baseUrl = (string) (config('app.url') ?: url('/'));
        }

        if (!preg_match('/^https?:\/\//i', $baseUrl)) {
            $baseUrl = 'https://' . $baseUrl;
        }

        return rtrim($baseUrl, '/');
    }

    private function absoluteUrl(string $pathOrUrl, string $baseUrl): string
    {
        $value = trim($pathOrUrl);

        if (preg_match('/^https?:\/\//i', $value)) {
            return rtrim($value, '/');
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($value, '/');
    }

    private function formatLastmod(mixed $value): ?string
    {
        if ($value instanceof Carbon) {
            return $value->toAtomString();
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->toAtomString();
        }

        return null;
    }

    private function renderPlaceholders(string $content, SiteSetting $settings): string
    {
        $baseUrl = $this->baseUrl($settings);

        return strtr($content, [
            '{site_name}' => (string) ($settings->site_name ?: config('app.name')),
            '{site_url}' => $baseUrl,
            '{sitemap_url}' => $baseUrl . '/sitemap.xml',
            '{robots_url}' => $baseUrl . '/robots.txt',
            '{generated_at}' => now()->toDateTimeString(),
        ]);
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
