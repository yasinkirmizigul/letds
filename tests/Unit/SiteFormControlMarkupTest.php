<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class SiteFormControlMarkupTest extends TestCase
{
    #[DataProvider('siteTemplateProvider')]
    public function test_every_public_site_select_uses_the_ktui_select_component(string $path): void
    {
        $source = file_get_contents($path);

        $this->assertNotFalse($source);

        preg_match_all('/<select\b[^>]*>/is', $source, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as [$tag, $offset]) {
            $line = substr_count(substr($source, 0, $offset), "\n") + 1;

            $this->assertMatchesRegularExpression(
                '/\bclass\s*=\s*["\'][^"\']*\bkt-select\b[^"\']*["\']/i',
                $tag,
                sprintf('%s:%d does not use the KTUI select class.', $path, $line),
            );
            $this->assertMatchesRegularExpression(
                '/\bdata-kt-select\s*=\s*["\']true["\']/i',
                $tag,
                sprintf('%s:%d still renders a native select.', $path, $line),
            );
        }
    }

    public static function siteTemplateProvider(): array
    {
        $root = dirname(__DIR__, 2);
        $directory = $root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'site';
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        $cases = [];

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $path = $file->getPathname();
            if (! str_contains(file_get_contents($path), '<select')) {
                continue;
            }

            $relativePath = str_replace($root.DIRECTORY_SEPARATOR, '', $path);
            $cases[$relativePath] = [$path];
        }

        return $cases;
    }
}
