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
