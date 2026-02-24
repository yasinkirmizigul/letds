<?php

namespace Database\Seeders;

use App\Models\Admin\User\Permission;
use App\Models\Admin\User\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $slugs = [
            'admin.access',

            // Users / Roles / Permissions
            'users.view','users.create','users.update','users.delete',
            'roles.view','roles.create','roles.update','roles.delete',
            'permissions.view','permissions.create','permissions.update','permissions.delete',

            // Projects
            'projects.view','projects.create','projects.update','projects.delete',
            'projects.trash','projects.restore','projects.force_delete','projects.state_change',
        ];

        // ✅ Module permissions auto-load
        $modulesDir = database_path('seeders/permissions/modules');
        if (is_dir($modulesDir)) {
            foreach (glob($modulesDir.'/*.php') as $f) {
                $extra = require $f;
                if (is_array($extra)) {
                    $slugs = array_merge($slugs, $extra);
                }
            }
        }

        $slugs = array_values(array_unique($slugs));

        foreach ($slugs as $slug) {
            Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => ucfirst(str_replace('.', ' ', $slug))]
            );
        }

        // ✅ UI parity + otomatik: Super Admin -> hepsi
        $super = Role::where('slug', 'superadmin')->first();
        if ($super) {
            $super->permissions()->sync(Permission::pluck('id')->all());
        }

        $this->command?->info('PermissionSeeder: permission listesi hazır (+ superadmin sync).');
    }
}
