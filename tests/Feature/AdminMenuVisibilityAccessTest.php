<?php

namespace Tests\Feature;

use App\Support\Admin\AdminMenuRegistry;
use Tests\TestCase;

class AdminMenuVisibilityAccessTest extends TestCase
{
    public function test_guests_are_redirected_to_the_admin_login(): void
    {
        $this->get('/admin/menu-visibility')
            ->assertRedirect('/login');
    }

    public function test_the_configured_menu_catalog_has_stable_keys(): void
    {
        $registry = app(AdminMenuRegistry::class);

        $this->assertCount(7, $registry->all());
        $this->assertCount(39, $registry->availableKeys());
    }
}
