<?php

namespace Database\Seeders;

use App\Models\Admin\User\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $slugs = [
            'admin.access',

            'users.view','users.create','users.update','users.delete',
            'roles.view','roles.create','roles.update','roles.delete',
            'permissions.view','permissions.create','permissions.update','permissions.delete',
        ];

        foreach ($slugs as $slug) {
            Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => ucfirst(str_replace('.', ' ', $slug))]
            );
        }

        $this->command?->info('PermissionSeeder: permission listesi hazır.');
    }
}
