<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User\Role;
use App\Models\User\User;
use App\Models\User\Permission;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin']
        );

        // Admin -> sadece roles & permissions (users yok)
        $adminPerms = Permission::whereIn('slug', [
            'admin.access',

            'roles.view','roles.create','roles.update','roles.delete',
            'permissions.view','permissions.create','permissions.update','permissions.delete',
        ])->pluck('id')->all();

        $admin->permissions()->sync($adminPerms);

        $user = User::firstOrCreate(
            ['email' => 'admin2@admin.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('123456'),
                'is_active' => true,
            ]
        );

        $user->roles()->syncWithoutDetaching([$admin->id]);

        $this->command?->info('AdminSeeder: admin kullanıcı/rol hazır.');
    }
}
