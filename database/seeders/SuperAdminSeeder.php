<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User\Role;
use App\Models\User\User;
use App\Models\User\Permission;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $super = Role::firstOrCreate(
            ['slug' => 'superadmin'],
            ['name' => 'Super Admin']
        );

        // Superadmin -> tüm permissionlar
        $super->permissions()->sync(Permission::pluck('id')->all());

        $user = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('123456'),
                'is_active' => true,
            ]
        );

        $user->roles()->syncWithoutDetaching([$super->id]);

        $this->command?->info('SuperAdminSeeder: superadmin kullanıcı/rol hazır.');
    }
}
