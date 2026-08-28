<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class AdminFormControlMarkupTest extends TestCase
{
    #[DataProvider('adminTemplateProvider')]
    public function test_every_admin_select_uses_the_ktui_select_component(string $path): void
    {
        $source = file_get_contents($path);

        $this->assertNotFalse($source);

        preg_match_all('/<select\b[^>]*>/is', $source, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as [$tag, $offset]) {
            $line = substr_count(substr($source, 0, $offset), "\n") + 1;

            $this->assertMatchesRegularExpression(
                '/\bdata-kt-select\s*=\s*["\']true["\']/i',
                $tag,
                sprintf('%s:%d still renders a native select.', $path, $line),
            );
        }
    }

    #[DataProvider('adminTemplateProvider')]
    public function test_admin_templates_do_not_use_native_date_or_time_inputs(string $path): void
    {
        $source = file_get_contents($path);

        $this->assertNotFalse($source);
        $this->assertDoesNotMatchRegularExpression(
            '/<input\b[^>]*\btype\s*=\s*["\'](?:date|datetime-local|time)["\']/i',
            $source,
            $path.' still uses a browser-native date or time input.',
        );
    }

    public static function adminTemplateProvider(): array
    {
        $root = dirname(__DIR__, 2);
        $directories = [
            $root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'admin',
            $root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'admin',
        ];
        $cases = [];

        foreach ($directories as $directory) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                    continue;
                }

                $path = $file->getPathname();
                $relativePath = str_replace($root.DIRECTORY_SEPARATOR, '', $path);
                $cases[$relativePath] = [$path];
            }
        }

        return $cases;
    }
}
