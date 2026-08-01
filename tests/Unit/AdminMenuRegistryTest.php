<?php

namespace Tests\Unit;

use App\Support\Admin\AdminMenuRegistry;
use PHPUnit\Framework\TestCase;

class AdminMenuRegistryTest extends TestCase
{
    public function test_it_hides_individual_children_and_keeps_the_parent(): void
    {
        $registry = new AdminMenuRegistry;
        $menu = $registry->visibleMenu(['content.pages'], $this->menu());

        $this->assertCount(1, $menu);
        $this->assertSame('content', $menu[0]['key']);
        $this->assertSame(['content.blog'], array_column($menu[0]['children'], 'key'));
        $this->assertSame(['blog.view'], $menu[0]['permAny']);
    }

    public function test_it_hides_a_parent_with_all_of_its_children(): void
    {
        $registry = new AdminMenuRegistry;

        $this->assertSame([], $registry->visibleMenu(['content'], $this->menu()));
    }

    public function test_it_drops_an_accordion_when_every_child_is_hidden(): void
    {
        $registry = new AdminMenuRegistry;

        $this->assertSame([], $registry->visibleMenu(
            ['content.pages', 'content.blog'],
            $this->menu()
        ));
    }

    public function test_visible_keys_include_only_effectively_visible_items(): void
    {
        $registry = new AdminMenuRegistry;

        $this->assertSame(
            ['content', 'content.blog'],
            $registry->visibleKeys(['content.pages'], $this->menu())
        );

        $this->assertSame([], $registry->visibleKeys(['content'], $this->menu()));
    }

    private function menu(): array
    {
        return [
            [
                'key' => 'content',
                'type' => 'accordion',
                'title' => 'İçerik',
                'permAny' => ['pages.view', 'blog.view'],
                'children' => [
                    [
                        'key' => 'content.pages',
                        'title' => 'Sayfalar',
                        'route' => 'admin.pages.index',
                        'perm' => 'pages.view',
                    ],
                    [
                        'key' => 'content.blog',
                        'title' => 'Yazılar',
                        'route' => 'admin.blog.index',
                        'perm' => 'blog.view',
                    ],
                ],
            ],
        ];
    }
}
