<?php

namespace Tests\Feature;

use App\Models\Admin\User\Role;
use App\Models\Admin\User\User;
use App\Models\Site\SiteNavigationItem;
use App\Support\Site\NavigationTree;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteNavigationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_site_links_are_seeded_as_panel_managed_items(): void
    {
        $this->assertDatabaseHas('site_navigation_items', [
            'location' => SiteNavigationItem::LOCATION_PRIMARY,
            'title' => 'Ana Sayfa',
            'link_type' => SiteNavigationItem::LINK_TYPE_ROUTE,
            'route_name' => 'site.home',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('site_navigation_items', [
            'location' => SiteNavigationItem::LOCATION_FOOTER,
            'title' => 'İletişim',
            'route_name' => 'site.contact-messages.create',
        ]);
        $this->assertDatabaseHas('site_navigation_items', [
            'location' => SiteNavigationItem::LOCATION_PRIMARY,
            'title' => 'Sıkça Sorulan Sorular',
            'route_name' => 'site.faqs.index',
        ]);
    }

    public function test_public_header_mobile_menu_and_footer_use_only_panel_items(): void
    {
        SiteNavigationItem::query()->delete();

        SiteNavigationItem::query()->create([
            'location' => SiteNavigationItem::LOCATION_PRIMARY,
            'title' => 'Panelden Blog',
            'link_type' => SiteNavigationItem::LINK_TYPE_ROUTE,
            'route_name' => 'site.blog.index',
            'target' => SiteNavigationItem::TARGET_SELF,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        SiteNavigationItem::query()->create([
            'location' => SiteNavigationItem::LOCATION_PRIMARY,
            'title' => 'Gizli Galeri',
            'link_type' => SiteNavigationItem::LINK_TYPE_ROUTE,
            'route_name' => 'site.galleries.index',
            'target' => SiteNavigationItem::TARGET_SELF,
            'is_active' => false,
            'sort_order' => 2,
        ]);
        SiteNavigationItem::query()->create([
            'location' => SiteNavigationItem::LOCATION_FOOTER,
            'title' => 'Panelden İletişim',
            'link_type' => SiteNavigationItem::LINK_TYPE_ROUTE,
            'route_name' => 'site.contact-messages.create',
            'target' => SiteNavigationItem::TARGET_SELF,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->get(route('site.blog.index'))
            ->assertOk()
            ->assertSee('data-site-primary-navigation', false)
            ->assertSee('data-site-mobile-navigation', false)
            ->assertSee('data-site-footer-navigation', false)
            ->assertSee('Panelden Blog')
            ->assertSee('Panelden İletişim')
            ->assertDontSee('Gizli Galeri')
            ->assertDontSee('Ana Sayfa');
    }

    public function test_invalid_system_routes_are_not_rendered(): void
    {
        SiteNavigationItem::query()->delete();

        SiteNavigationItem::query()->create([
            'location' => SiteNavigationItem::LOCATION_PRIMARY,
            'title' => 'Geçersiz Sistem Bağlantısı',
            'link_type' => SiteNavigationItem::LINK_TYPE_ROUTE,
            'route_name' => 'site.unknown',
            'target' => SiteNavigationItem::TARGET_SELF,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->assertTrue(
            NavigationTree::forLocation(SiteNavigationItem::LOCATION_PRIMARY, true)->isEmpty()
        );
    }

    public function test_superadmin_can_create_a_system_page_navigation_item(): void
    {
        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => 'superadmin',
        ]);
        $user = User::query()->create([
            'name' => 'Navigation Test Admin',
            'email' => 'navigation-admin@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $user->roles()->attach($role);

        $this->actingAs($user)
            ->get(route('admin.site.navigation.index'))
            ->assertOk()
            ->assertSee('Sistem Sayfası')
            ->assertSee('site.blog.index', false)
            ->assertSee('site.faqs.index', false);

        $this->actingAs($user)
            ->post(route('admin.site.navigation.store'), [
                'location' => SiteNavigationItem::LOCATION_PRIMARY,
                'title' => 'Yazılar',
                'link_type' => SiteNavigationItem::LINK_TYPE_ROUTE,
                'route_name' => 'site.blog.index',
                'target' => SiteNavigationItem::TARGET_SELF,
                'is_active' => '1',
                'translations' => [],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('site_navigation_items', [
            'title' => 'Yazılar',
            'link_type' => SiteNavigationItem::LINK_TYPE_ROUTE,
            'route_name' => 'site.blog.index',
        ]);
    }
}
