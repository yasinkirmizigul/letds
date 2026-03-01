<?php

namespace Database\Seeders;

use App\Models\Admin\User\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // slug => human name
        $permissions = [
            // Admin
            'admin.access' => 'Admin Access',

            // Users / Roles / Permissions
            'users.view' => 'Users View',
            'users.create' => 'Users Create',
            'users.update' => 'Users Update',
            'users.delete' => 'Users Delete',

            'roles.view' => 'Roles View',
            'roles.create' => 'Roles Create',
            'roles.update' => 'Roles Update',
            'roles.delete' => 'Roles Delete',

            'permissions.view' => 'Permissions View',
            'permissions.create' => 'Permissions Create',
            'permissions.update' => 'Permissions Update',
            'permissions.delete' => 'Permissions Delete',

            // Projects
            'projects.view' => 'Projects View',
            'projects.create' => 'Projects Create',
            'projects.update' => 'Projects Update',
            'projects.delete' => 'Projects Delete',
            'projects.trash' => 'Projects Trash',
            'projects.restore' => 'Projects Restore',
            'projects.force_delete' => 'Projects Force Delete',
            'projects.state_change' => 'Projects State Change',
        ];

        // Optional: extra permissions per module.
        // Supported formats (each module file should return array):
        // 1) ['blog.view', 'blog.create', ...]  -> name auto-generated
        // 2) ['blog.view' => 'Blog View', ...]  -> explicit names
        $modulesDir = database_path('seeders/permissions/modules');
        if (is_dir($modulesDir)) {
            foreach (glob($modulesDir . '/*.php') as $f) {
                $extra = require $f;

                if (!is_array($extra)) {
                    continue;
                }

                foreach ($extra as $k => $v) {
                    if (is_int($k)) {
                        // list of slugs
                        $slug = (string) $v;
                        $permissions[$slug] = $permissions[$slug] ?? $this->defaultNameFromSlug($slug);
                    } else {
                        // slug => name
                        $permissions[(string) $k] = (string) $v;
                    }
                }
            }
        }

        // Upsert (idempotent)
        foreach ($permissions as $slug => $name) {
            Permission::updateOrCreate(
                ['slug' => $slug],
                ['name' => $name]
            );
        }

        $this->command?->info('PermissionSeeder: permissions upserted (' . count($permissions) . ').');
    }

    private function defaultNameFromSlug(string $slug): string
    {
        $slug = trim($slug);
        if ($slug === '') return 'Permission';

        $parts = preg_split('/[._-]+/', $slug) ?: [$slug];
        $parts = array_map(fn ($p) => ucfirst(strtolower($p)), $parts);

        return implode(' ', $parts);
    }
}
