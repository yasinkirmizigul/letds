<?php

namespace Tests\Feature;

use App\Models\Admin\User\Role;
use App\Models\Admin\User\User;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardIntendedUrlIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_intended_url_cannot_hijack_admin_login(): void
    {
        $admin = $this->createAdmin();

        $this->get(route('member.appointments.index'))
            ->assertRedirect(route('member.login'));

        $this->post(route('login.post'), [
            'email' => $admin->email,
            'password' => 'password',
        ])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionMissing('url.intended');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_intended_url_cannot_hijack_member_login(): void
    {
        $member = Member::query()->create([
            'name' => 'Member',
            'surname' => 'Test',
            'email' => 'member-intended@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);

        $this->withSession(['url.intended' => route('admin.dashboard')])
            ->post(route('member.login.post'), [
                'email' => $member->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('member.appointments.index'))
            ->assertSessionMissing('url.intended');

        $this->assertAuthenticatedAs($member, 'member');
    }

    public function test_each_guard_keeps_its_own_valid_intended_url(): void
    {
        $admin = $this->createAdmin();
        $adminTarget = route('admin.dashboard', ['source' => 'login']);

        $this->withSession(['url.intended' => $adminTarget])
            ->post(route('login.post'), [
                'email' => $admin->email,
                'password' => 'password',
            ])
            ->assertRedirect($adminTarget);

        auth()->logout();

        $member = Member::query()->create([
            'name' => 'Member',
            'surname' => 'Target',
            'email' => 'member-target@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $memberTarget = route('member.appointments.index', ['step' => 2]);

        $this->withSession(['url.intended' => $memberTarget])
            ->post(route('member.login.post'), [
                'email' => $member->email,
                'password' => 'password',
            ])
            ->assertRedirect($memberTarget);
    }

    private function createAdmin(): User
    {
        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => 'superadmin',
        ]);

        $admin = User::query()->create([
            'name' => 'Redirect Test Admin',
            'email' => 'redirect-admin@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $admin->roles()->attach($role);

        return $admin;
    }
}
