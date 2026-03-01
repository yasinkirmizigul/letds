<?php

namespace Database\Seeders;

use App\Models\Admin\User\Permission;
use App\Models\Admin\User\Role;
use App\Models\Admin\User\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::updateOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin']
        );

        // Admin -> only auth management (no users module by default)
        $adminPerms = Permission::whereIn('slug', [
            'admin.access',
            'roles.view','roles.create','roles.update','roles.delete',
            'permissions.view','permissions.create','permissions.update','permissions.delete',
        ])->pluck('id')->all();

        $admin->permissions()->sync($adminPerms);

        if ($this->shouldCreateUsers()) {
            $email = env('SEED_ADMIN_EMAIL', 'admin2@admin.com');
            $name  = env('SEED_ADMIN_NAME', 'Admin');
            $pass  = env('SEED_ADMIN_PASS', '123456');

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make($pass),
                    'is_active' => true,
                ]
            );

            $user->roles()->syncWithoutDetaching([$admin->id]);
        } else {
            $this->command?->warn('AdminSeeder: user creation skipped (SEED_CREATE_USERS disabled).');
        }

        $this->command?->info('AdminSeeder: role synced (auth management perms).');
    }

    private function shouldCreateUsers(): bool
    {
        $default = !app()->environment('production');

        return filter_var(env('SEED_CREATE_USERS', $default), FILTER_VALIDATE_BOOL);
    }
}
