<?php

namespace Tests\Unit;

use App\Support\Site\SiteIcon;
use PHPUnit\Framework\TestCase;

class SiteIconTest extends TestCase
{
    public function test_it_converts_persisted_keenicons_to_bundled_font_awesome_icons(): void
    {
        $this->assertSame('fa-solid fa-house', SiteIcon::classes('ki-outline ki-home'));
        $this->assertSame('fa-solid fa-comments', SiteIcon::classes('ki-filled ki-messages'));
    }

    public function test_it_keeps_supported_font_awesome_icons_and_falls_back_safely(): void
    {
        $this->assertSame('fa-solid fa-magnifying-glass', SiteIcon::classes('fa-solid fa-magnifying-glass'));
        $this->assertSame('fa-solid fa-circle', SiteIcon::classes('ki-outline ki-unknown'));
        $this->assertSame('fa-solid fa-circle', SiteIcon::classes('fa-regular fa-address-book'));
    }
}
