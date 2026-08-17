<?php

namespace Tests\Unit;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ResponsiveMarkupTest extends TestCase
{
    public function test_frontend_sources_do_not_use_legacy_bootstrap_utilities(): void
    {
        $legacyUtilities = implode('|', [
            'd-flex',
            'd-grid',
            'd-none',
            'w-100',
            'h-100',
            'flex-column',
            'justify-content-(?:start|center|end|between)',
            'align-items-(?:start|center|end|stretch)',
            'position-(?:relative|absolute|fixed)',
            'rounded-pill',
        ]);
        $violations = [];

        foreach ($this->frontendSourceFiles() as $file) {
            $contents = file_get_contents($file);

            if (preg_match('/(?<![A-Za-z0-9_-])(?:'.$legacyUtilities.')(?![A-Za-z0-9_-])/', $contents)) {
                $violations[] = $this->relativePath($file);
            }
        }

        $this->assertSame([], $violations, 'Legacy Bootstrap utilities found in: '.implode(', ', $violations));
    }

    public function test_arbitrary_grid_templates_use_valid_column_separators(): void
    {
        $violations = [];

        foreach ($this->frontendSourceFiles() as $file) {
            $contents = file_get_contents($file);
            preg_match_all('/grid-cols-\[([^\]\s]+)\]/', $contents, $matches, PREG_OFFSET_CAPTURE);

            foreach ($matches[1] as [$template, $offset]) {
                if ($this->hasTopLevelComma($template)) {
                    $line = substr_count(substr($contents, 0, $offset), "\n") + 1;
                    $violations[] = $this->relativePath($file).':'.$line.' ['.$template.']';
                }
            }
        }

        $this->assertSame([], $violations, 'Invalid arbitrary grid templates found: '.implode(', ', $violations));
    }

    public function test_project_component_classes_have_css_definitions(): void
    {
        $css = file_get_contents($this->projectRoot().DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'app.css');
        $projectClasses = [
            'kt-alert-danger',
            'kt-alert-text',
            'kt-badge-danger',
            'kt-badge-light-primary',
            'kt-badge-light-success',
            'kt-badge-light-warning',
            'kt-badge-light-info',
            'kt-badge-light-danger',
            'kt-card-body',
            'kt-input-invalid',
            'kt-switch-mono',
        ];

        foreach ($projectClasses as $class) {
            $this->assertStringContainsString('.'.$class, $css, 'Missing CSS definition for '.$class);
        }
    }

    public function test_grid_cards_and_accordions_keep_their_natural_height(): void
    {
        $css = file_get_contents($this->projectRoot().DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'app.css');
        $homeCss = file_get_contents($this->projectRoot().DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'site'.DIRECTORY_SEPARATOR.'home'.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'home.css');

        $this->assertStringContainsString(
            '.grid > :is(.kt-card, .app-surface-card, details)',
            $css,
            'Grid cards and accordions must opt in to equal-height stretching.'
        );
        $this->assertStringContainsString('block-size: fit-content;', $css);
        $this->assertMatchesRegularExpression(
            '/\.home-discovery-layout\s*\{[^}]*align-items:\s*start;/s',
            $homeCss,
            'The homepage About and FAQ panels must keep independent heights.'
        );
    }

    public function test_badges_and_surface_controls_keep_text_inside_their_backgrounds(): void
    {
        $css = file_get_contents($this->projectRoot().DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'app.css');
        $surfaceEditor = file_get_contents(
            $this->projectRoot().DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.'site'.DIRECTORY_SEPARATOR.'homepage'.DIRECTORY_SEPARATOR.'partials'.DIRECTORY_SEPARATOR.'_surface-editor.blade.php'
        );

        $this->assertMatchesRegularExpression(
            '/\.kt-badge\s*\{[^}]*width:\s*fit-content;[^}]*max-width:\s*100%;[^}]*height:\s*auto;[^}]*flex:\s*0 0 auto;[^}]*overflow-wrap:\s*anywhere;[^}]*white-space:\s*normal;/s',
            $css,
            'Badges must contain long or wrapped labels without shrinking their background.'
        );
        $this->assertStringContainsString('container: homepage-surface / inline-size;', $css);
        $this->assertStringContainsString('@container homepage-surface (min-width: 30rem)', $css);
        $this->assertStringContainsString('homepage-surface-editor__range-grid', $surfaceEditor);
        $this->assertStringNotContainsString('sm:grid-cols-3', $surfaceEditor);
    }

    public function test_public_theme_and_probablue_brand_have_responsive_styles(): void
    {
        $css = file_get_contents($this->projectRoot().DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'app.css');
        $homeCss = file_get_contents($this->projectRoot().DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'site'.DIRECTORY_SEPARATOR.'home'.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'home.css');
        $homeView = file_get_contents($this->projectRoot().DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'site'.DIRECTORY_SEPARATOR.'home.blade.php');
        $lightBackground = file_get_contents($this->projectRoot().DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'site'.DIRECTORY_SEPARATOR.'home'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'home-background-light.svg');
        $darkBackground = file_get_contents($this->projectRoot().DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'site'.DIRECTORY_SEPARATOR.'home'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'home-background-dark.svg');

        $this->assertStringContainsString('body.site-shell .probablue-brand__tagline', $css);
        $this->assertStringContainsString('html[data-site-theme="dark"] body.site-shell .site-theme-toggle', $css);
        $this->assertStringContainsString('grid-template-areas:', $homeCss);
        $this->assertStringContainsString('"brand theme"', $homeCss);
        $this->assertStringContainsString('html[data-site-theme="dark"] .home-discovery-section', $homeCss);
        $this->assertStringContainsString('html[data-site-theme="dark"] .site-home-header .home-theme-toggle', $homeCss);
        $this->assertStringContainsString('html[data-site-theme="dark"] .site-home-header.sticky .home-theme-toggle', $homeCss);
        $this->assertMatchesRegularExpression('/\.site-theme-toggle\s*\{[^}]*width:\s*36px;[^}]*height:\s*36px;/s', $homeCss);
        $this->assertStringContainsString('.wrapper-logo.has-image', $homeCss);
        $this->assertStringContainsString('html.home-hero-pending .view-after', $homeCss);
        $this->assertStringContainsString('width: min(400px, calc(100vw - 64px));', $homeCss);
        $this->assertStringContainsString('color: #ffffff;', $homeCss);
        $this->assertStringContainsString('cursor: move;', $homeCss);
        $this->assertStringContainsString('.home-hero-float {', $homeCss);
        $this->assertStringContainsString('.home-hero-shadow {', $homeCss);
        $this->assertStringNotContainsString('et-anim="floating_special"', $homeView);
        $this->assertStringContainsString('class="icon-drag__arrow"', $homeView);
        $this->assertStringContainsString('width: 16px;', $homeCss);
        $this->assertStringNotContainsString('content: "‹";', $homeCss);
        $this->assertStringContainsString('--home-background-image-dark', $homeView);
        $this->assertStringContainsString('html[data-site-theme="dark"] .wrapper-after::before', $homeCss);
        $this->assertStringContainsString('viewBox="0 0 1920 1080"', $lightBackground);
        $this->assertStringContainsString('viewBox="0 0 1920 1080"', $darkBackground);
        $this->assertStringNotContainsString('<text', $lightBackground);
        $this->assertStringNotContainsString('<text', $darkBackground);
        $this->assertStringContainsString('body.site-shell .kt-select-dropdown', $css);
        $this->assertStringContainsString('background: var(--background) !important;', $css);
    }

    public function test_public_gallery_lightbox_is_viewport_centered_and_responsive(): void
    {
        $css = file_get_contents($this->projectRoot().DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'app.css');
        $script = file_get_contents($this->projectRoot().DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'site'.DIRECTORY_SEPARATOR.'gallery-lightbox.js');

        $this->assertMatchesRegularExpression(
            '/\.site-lightbox\s*\{[^}]*position:\s*fixed;[^}]*inset:\s*0;[^}]*width:\s*100dvw;[^}]*height:\s*100dvh;/s',
            $css
        );
        $this->assertStringContainsString('place-items: center;', $css);
        $this->assertMatchesRegularExpression(
            '/\.site-lightbox__viewport img\s*\{[^}]*position:\s*absolute;[^}]*inset:\s*var\(--site-lightbox-media-gap\);[^}]*max-width:\s*100%;[^}]*max-height:\s*100%;[^}]*object-fit:\s*contain;/s',
            $css
        );
        $this->assertStringContainsString("document.body.classList.add('site-lightbox-open')", $script);
        $this->assertStringContainsString("event.key === 'ArrowLeft'", $script);
        $this->assertStringContainsString("event.pointerType === 'touch'", $script);
    }

    public function test_public_inner_page_heroes_keep_a_compact_responsive_scale(): void
    {
        $css = file_get_contents($this->projectRoot().DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'app.css');
        $faqView = file_get_contents($this->projectRoot().DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'site'.DIRECTORY_SEPARATOR.'faqs'.DIRECTORY_SEPARATOR.'index.blade.php');

        $this->assertMatchesRegularExpression(
            '/\.site-page-hero\s*\{[^}]*gap:\s*clamp\(0\.8rem, 1\.4vw, 1\.1rem\);[^}]*padding:\s*clamp\(1\.6rem, 3vw, 2\.75rem\);/s',
            $css
        );
        $this->assertMatchesRegularExpression(
            '/\.site-title\s*\{[^}]*max-width:\s*18ch;[^}]*font-size:\s*clamp\(2\.15rem, 4\.2vw, 3\.9rem\);/s',
            $css
        );
        $this->assertStringContainsString('lg:py-14', $faqView);
        $this->assertStringNotContainsString('lg:py-20', $faqView);
        $this->assertStringNotContainsString('lg:text-6xl', $faqView);
    }

    public function test_notification_filters_use_ktui_selects_in_a_responsive_row(): void
    {
        $css = file_get_contents($this->projectRoot().DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'app.css');
        $view = file_get_contents($this->projectRoot().DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.'notifications'.DIRECTORY_SEPARATOR.'index.blade.php');

        $this->assertStringContainsString('admin-notification-filter-header', $view);
        $this->assertStringContainsString('admin-notification-filter-form', $view);
        $this->assertSame(2, substr_count($view, 'data-kt-select="true"'));
        $this->assertSame(2, substr_count($view, 'class="kt-select w-full"'));
        $this->assertMatchesRegularExpression(
            '/\.admin-notification-filter-form\s*\{[^}]*display:\s*grid;[^}]*grid-template-columns:\s*11\.25rem 12\.5rem auto;/s',
            $css
        );
    }

    public function test_admin_workspaces_use_distinct_create_and_collection_surfaces(): void
    {
        $root = $this->projectRoot().DIRECTORY_SEPARATOR;
        $css = file_get_contents($root.'resources'.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'app.css');
        $script = file_get_contents($root.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'semantic-panels.js');
        $appScript = file_get_contents($root.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'app.js');
        $layout = file_get_contents($root.'resources'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'layouts'.DIRECTORY_SEPARATOR.'main'.DIRECTORY_SEPARATOR.'app.blade.php');
        $listLayout = file_get_contents($root.'resources'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'list-layout.blade.php');

        foreach ([
            '--admin-create-header',
            '--admin-create-surface',
            '--admin-create-field',
            '--admin-collection-header',
            '--admin-collection-surface',
            '--admin-collection-field',
            '--admin-sidebar-surface',
            '--admin-card-header-surface',
            '--admin-card-header-border',
            '.admin-panel--create',
            '.admin-panel--collection',
            '.admin-collection-stack',
        ] as $requiredStyle) {
            $this->assertStringContainsString($requiredStyle, $css);
        }

        $this->assertStringContainsString('.dark body.dash_app', $css);
        $this->assertStringContainsString('CREATE_TITLE_PATTERN', $script);
        $this->assertStringContainsString('COLLECTION_TITLE_PATTERN', $script);
        $this->assertStringContainsString('new MutationObserver', $script);
        $this->assertStringContainsString("import initAdminSemanticPanels from './helpers/semantic-panels';", $appScript);
        $this->assertStringContainsString('data-admin-page-mode="{{ $adminPageMode }}"', $layout);
        $this->assertStringContainsString('admin-panel--collection', $listLayout);
    }

    public function test_appointment_day_status_and_card_headers_do_not_wrap_or_merge_with_bodies(): void
    {
        $root = $this->projectRoot().DIRECTORY_SEPARATOR;
        $css = file_get_contents($root.'resources'.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'app.css');
        $script = file_get_contents($root.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.'appointments'.DIRECTORY_SEPARATOR.'settings.js');
        $view = file_get_contents($root.'resources'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.'appointments'.DIRECTORY_SEPARATOR.'settings.blade.php');

        $this->assertStringNotContainsString('Çalışma açık', $script);
        $this->assertStringContainsString('appointment-day-toggle__status', $script);
        $this->assertStringContainsString('aria-label="${escapeHtml(day.label)} çalışma durumunu değiştir"', $script);
        $this->assertStringContainsString('min-w-[140px] whitespace-nowrap', $view);
        $this->assertMatchesRegularExpression(
            '/\.appointment-day-toggle\s*\{[^}]*min-width:\s*6\.75rem;[^}]*white-space:\s*nowrap;/s',
            $css
        );
        $this->assertMatchesRegularExpression(
            '/body\.dash_app :is\(\.kt-card, \.app-surface-card\) > \.kt-card-header[^}]*var\(--admin-card-header-surface\)/s',
            $css
        );
    }

    public function test_dashboard_and_site_text_controls_have_a_visible_border_contract(): void
    {
        $css = file_get_contents($this->projectRoot().DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'app.css');
        $violations = [];
        $textareaCount = 0;

        foreach ($this->frontendSourceFiles() as $file) {
            $contents = file_get_contents($file);
            $normalised = preg_replace('/{{.*?}}/s', 'BLADE_EXPRESSION', $contents);
            $normalised = preg_replace('/\$\{.*?}/s', 'JS_EXPRESSION', $normalised);
            preg_match_all('/<textarea\b[^>]*>/is', $normalised, $matches);

            foreach ($matches[0] as $textarea) {
                $textareaCount++;

                if (!preg_match('/class\s*=\s*["\'][^"\']*(?:kt-input|kt-textarea)[^"\']*["\']/i', $textarea)) {
                    $violations[] = $this->relativePath($file).' => '.preg_replace('/\s+/', ' ', trim($textarea));
                }
            }
        }

        $this->assertGreaterThan(30, $textareaCount, 'The textarea audit unexpectedly scanned too few controls.');
        $this->assertSame([], $violations, 'Textareas without the shared border contract: '.implode(', ', $violations));
        $this->assertMatchesRegularExpression(
            '/\.kt-input,\s*\.kt-textarea,\s*\.kt-select,[^{]*\{[^}]*border:\s*1px solid var\(--border\);/s',
            $css
        );
        $this->assertStringContainsString('--app-form-control-border:', $css);
        $this->assertStringContainsString(':where(body.dash_app, body.site-shell)', $css);
        $this->assertStringContainsString('.homepage-color-control > input', $css);
    }

    public function test_admin_preview_link_and_global_title_tooltips_are_available(): void
    {
        $root = $this->projectRoot().DIRECTORY_SEPARATOR;
        $header = file_get_contents($root.'resources'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'layouts'.DIRECTORY_SEPARATOR.'main'.DIRECTORY_SEPARATOR.'header.blade.php');
        $tooltipScript = file_get_contents($root.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR.'title-tooltips.js');
        $adminScript = file_get_contents($root.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'app.js');
        $siteScript = file_get_contents($root.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'site'.DIRECTORY_SEPARATOR.'app.js');
        $homeTooltipScript = file_get_contents($root.'resources'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'site'.DIRECTORY_SEPARATOR.'title-tooltips.js');
        $homeView = file_get_contents($root.'resources'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'site'.DIRECTORY_SEPARATOR.'home.blade.php');
        $css = file_get_contents($root.'resources'.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'app.css');
        $tooltipCss = file_get_contents($root.'resources'.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'title-tooltips.css');
        $viteConfig = file_get_contents($root.'vite.config.js');

        $this->assertStringContainsString("route('site.home')", $header);
        $this->assertStringContainsString('target="_blank"', $header);
        $this->assertStringContainsString('rel="noopener noreferrer"', $header);
        $this->assertStringContainsString('title="Siteyi önizle"', $header);
        $this->assertStringContainsString('aria-label="Siteyi önizle"', $header);
        $this->assertStringContainsString('ki-outline ki-eye', $header);

        $this->assertStringContainsString("import initTitleTooltips from '@/core/title-tooltips';", $adminScript);
        $this->assertStringContainsString("import initTitleTooltips from '@/core/title-tooltips';", $siteScript);
        $this->assertStringContainsString("import initTitleTooltips from '@/core/title-tooltips';", $homeTooltipScript);
        $this->assertStringContainsString('initTitleTooltips(document);', $adminScript);
        $this->assertStringContainsString('initTitleTooltips(document);', $siteScript);
        $this->assertStringContainsString('initTitleTooltips(document)', $homeTooltipScript);
        $this->assertStringContainsString("@vite(['resources/css/title-tooltips.css', 'resources/js/site/title-tooltips.js'])", $homeView);
        $this->assertStringContainsString("'resources/css/title-tooltips.css'", $viteConfig);
        $this->assertStringContainsString("'resources/js/site/title-tooltips.js'", $viteConfig);
        $this->assertStringContainsString('new MutationObserver', $tooltipScript);
        $this->assertStringContainsString("element.removeAttribute('title')", $tooltipScript);
        $this->assertStringContainsString("tooltip.setAttribute('role', 'tooltip')", $tooltipScript);
        $this->assertStringContainsString("element.setAttribute('aria-describedby'", $tooltipScript);
        $this->assertStringContainsString("event.key === 'Escape'", $tooltipScript);

        $this->assertStringContainsString('@import "./title-tooltips.css";', $css);
        $this->assertStringContainsString('.app-title-tooltip {', $tooltipCss);
        $this->assertStringContainsString('.app-title-tooltip.is-visible', $tooltipCss);
        $this->assertStringContainsString('.app-title-tooltip[data-placement="bottom"]', $tooltipCss);
        $this->assertStringContainsString('html[data-site-theme="dark"] body.site-shell', $tooltipCss);
        $this->assertStringContainsString('html[data-site-theme="dark"] body.site-home-index', $tooltipCss);
    }

    private function frontendSourceFiles(): array
    {
        $files = [];

        foreach (['resources/views', 'resources/js'] as $relativeDirectory) {
            $directory = $this->projectRoot().DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativeDirectory);
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (str_ends_with($file->getFilename(), '.blade.php') || $file->getExtension() === 'js') {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    private function hasTopLevelComma(string $template): bool
    {
        $parenthesisDepth = 0;

        foreach (str_split($template) as $character) {
            if ($character === '(') {
                $parenthesisDepth++;
            } elseif ($character === ')') {
                $parenthesisDepth = max(0, $parenthesisDepth - 1);
            } elseif ($character === ',' && $parenthesisDepth === 0) {
                return true;
            }
        }

        return false;
    }

    private function relativePath(string $path): string
    {
        return str_replace($this->projectRoot().DIRECTORY_SEPARATOR, '', $path);
    }

    private function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}
