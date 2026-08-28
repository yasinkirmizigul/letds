<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class FaviconAssetsTest extends TestCase
{
    public function test_site_and_admin_svg_favicons_use_the_probablue_pv_mark(): void
    {
        $source = file_get_contents($this->publicPath('assets/site/home/images/p-v.svg'));
        $siteFavicon = file_get_contents($this->publicPath('assets/site/images/favicon.svg'));
        $adminFavicon = file_get_contents($this->publicPath('assets/admin/media/app/favicon.svg'));

        $this->assertNotFalse($source);
        $this->assertNotFalse($siteFavicon);
        $this->assertSame($siteFavicon, $adminFavicon);
        $this->assertStringContainsString('viewBox="0 0 450.2 450.2"', $siteFavicon);
        $this->assertStringContainsString('<title>Probablue P–V</title>', $siteFavicon);
        $this->assertStringContainsString('M.8,0v5.3c4,.2', $source);
        $this->assertStringContainsString('M.8,0v5.3c4,.2', $siteFavicon);
        $this->assertStringNotContainsString('viewBox="0 0 73.53 61.72"', $siteFavicon);
    }

    public function test_png_and_ico_fallbacks_are_real_images_at_the_expected_sizes(): void
    {
        foreach ([
            'assets/site/images/favicon-32x32.png',
            'assets/admin/media/app/favicon-32x32.png',
        ] as $path) {
            $size = getimagesize($this->publicPath($path));

            $this->assertNotFalse($size);
            $this->assertSame([32, 32], array_slice($size, 0, 2));
            $this->assertSame(IMAGETYPE_PNG, $size[2]);
        }

        $appleTouchSize = getimagesize($this->publicPath('apple-touch-icon.png'));

        $this->assertNotFalse($appleTouchSize);
        $this->assertSame([180, 180], array_slice($appleTouchSize, 0, 2));
        $this->assertSame(IMAGETYPE_PNG, $appleTouchSize[2]);

        $ico = file_get_contents($this->publicPath('favicon.ico'));

        $this->assertNotFalse($ico);
        $this->assertGreaterThan(1000, strlen($ico));
        $this->assertSame(
            ['reserved' => 0, 'type' => 1, 'count' => 3],
            unpack('vreserved/vtype/vcount', substr($ico, 0, 6))
        );
    }

    private function publicPath(string $path): string
    {
        return dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);
    }
}
