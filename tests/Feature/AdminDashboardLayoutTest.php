<?php

namespace Tests\Feature;

use App\Models\Admin\User\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_one_two_and_three_column_dashboard_rows(): void
    {
        $user = $this->superAdmin();
        $layoutRows = [
            ['hero_overview'],
            ['recent_messages', 'upcoming_appointments'],
            ['recent_content', 'risk_center', 'audit_issues'],
        ];

        $this->actingAs($user)
            ->put(route('admin.dashboard.manage.update'), [
                'action' => 'save',
                'visible_sections' => [
                    'hero_overview',
                    'recent_messages',
                    'upcoming_appointments',
                    'recent_content',
                    'risk_center',
                    'audit_issues',
                ],
                'section_order' => collect($layoutRows)->flatten()->all(),
                'layout_rows' => $layoutRows,
            ])
            ->assertRedirect(route('admin.dashboard.manage'))
            ->assertSessionHasNoErrors();

        $preference = $user->dashboardPreference()->firstOrFail();
        $this->assertSame($layoutRows, array_slice($preference->layout_rows, 0, 3));

        $response = $this->actingAs($user)->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertSee('data-dashboard-block="hero_overview"', false);
        $response->assertSee('data-dashboard-layout-columns="1"', false);
        $response->assertSee('data-dashboard-block="recent_messages"', false);
        $response->assertSee('data-dashboard-layout-columns="2"', false);
        $response->assertSee('--dashboard-grid-span: 3;', false);
        $response->assertSee('data-dashboard-block="recent_content"', false);
        $response->assertSee('data-dashboard-layout-columns="3"', false);
        $response->assertSee('--dashboard-grid-span: 2;', false);
    }

    public function test_dashboard_row_rejects_more_than_three_blocks(): void
    {
        $user = $this->superAdmin();

        $this->actingAs($user)
            ->from(route('admin.dashboard.manage'))
            ->put(route('admin.dashboard.manage.update'), [
                'action' => 'save',
                'visible_sections' => ['hero_overview'],
                'section_order' => ['hero_overview', 'kpi_overview', 'module_overview', 'recent_content'],
                'layout_rows' => [[
                    'hero_overview',
                    'kpi_overview',
                    'module_overview',
                    'recent_content',
                ]],
            ])
            ->assertRedirect(route('admin.dashboard.manage'))
            ->assertSessionHasErrors('layout_rows.0');

        $this->assertDatabaseCount('admin_dashboard_preferences', 0);
    }

    public function test_manage_page_exposes_drag_and_drop_layout_controls(): void
    {
        $response = $this->actingAs($this->superAdmin())->get(route('admin.dashboard.manage'));

        $response->assertOk();
        $response->assertSee('data-dashboard-layout-builder', false);
        $response->assertSee('data-dashboard-layout-row', false);
        $response->assertSee('name="layout_rows[', false);
        $response->assertSee('Her satır en fazla', false);
    }

    public function test_legacy_preference_update_without_layout_rows_preserves_the_existing_group(): void
    {
        $user = $this->superAdmin();
        $user->dashboardPreference()->create([
            'visible_sections' => [
                'recent_messages' => true,
                'upcoming_appointments' => true,
                'recent_content' => true,
            ],
            'section_order' => ['recent_messages', 'upcoming_appointments', 'recent_content'],
            'layout_rows' => [['recent_messages', 'upcoming_appointments']],
        ]);

        $this->actingAs($user)
            ->put(route('admin.dashboard.manage.update'), [
                'action' => 'save',
                'visible_sections' => ['recent_messages', 'upcoming_appointments', 'recent_content'],
                'section_order' => ['recent_messages', 'upcoming_appointments', 'recent_content'],
            ])
            ->assertRedirect(route('admin.dashboard.manage'))
            ->assertSessionHasNoErrors();

        $this->assertSame(
            ['recent_messages', 'upcoming_appointments'],
            $user->dashboardPreference()->firstOrFail()->layout_rows[0]
        );
    }

    private function superAdmin(): User
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(SuperAdminSeeder::class);

        return User::query()
            ->whereHas('roles', fn ($query) => $query->where('slug', 'superadmin'))
            ->firstOrFail();
    }
}
