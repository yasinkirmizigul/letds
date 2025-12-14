<?php

namespace Database\Seeders;

use App\Models\User\Permission;
use App\Models\User\Role;
use App\Models\User\User;
use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AuthSeeder extends Seeder
{
    public function run()
    {
        $superAdminRole = Role::create([
            'name' => 'Super Admin',
            'slug' => 'superadmin'
        ]);

        $permissions = [
            'blog.view',
            'blog.create',
            'blog.edit',
            'pricing.view',
            'users.manage',
        ];

        foreach ($permissions as $perm) {
            Permission::create([
                'name' => ucfirst(str_replace('.', ' ', $perm)),
                'slug' => $perm
            ]);
        }

        // Superadmin her şeye sahip
        $superAdminRole->permissions()->sync(Permission::all());

        // İlk kullanıcı
        $user = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@admin.com',
            'password' => bcrypt('123456'),
        ]);

        $user->roles()->attach($superAdminRole);
    }
}

