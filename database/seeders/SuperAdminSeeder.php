<?php

namespace Database\Seeders;

use App\Models\Admin\User\Permission;
use App\Models\Admin\User\Role;
use App\Models\Admin\User\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $super = Role::updateOrCreate(
            ['slug' => 'superadmin'],
            ['name' => 'Super Admin']
        );

        // Always keep SuperAdmin as "all permissions"
        $super->permissions()->sync(Permission::pluck('id')->all());

        if ($this->shouldCreateUsers()) {
            $email = env('SEED_SUPERADMIN_EMAIL', 'admin@admin.com');
            $name  = env('SEED_SUPERADMIN_NAME', 'Super Admin');
            $pass  = env('SEED_SUPERADMIN_PASS', '123456');

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make($pass),
                    'is_active' => true,
                ]
            );

            $user->roles()->syncWithoutDetaching([$super->id]);
        } else {
            $this->command?->warn('SuperAdminSeeder: user creation skipped (SEED_CREATE_USERS disabled).');
        }

        $this->command?->info('SuperAdminSeeder: role synced to all permissions.');
    }

    private function shouldCreateUsers(): bool
    {
        $default = !app()->environment('production');

        return filter_var(env('SEED_CREATE_USERS', $default), FILTER_VALIDATE_BOOL);
    }
}
