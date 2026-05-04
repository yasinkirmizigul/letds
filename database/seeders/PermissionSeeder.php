<?php

namespace Database\Seeders;

use App\Models\Admin\User\Permission;
use App\Support\Rbac;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // slug => human name
        $permissions = [
            // Admin
            'admin.access' => 'Panel Erişimi',

            // Users / Roles / Permissions
            'users.view' => 'Kullanıcıları Görüntüleme',
            'users.create' => 'Kullanıcı Oluşturma',
            'users.update' => 'Kullanıcı Güncelleme',
            'users.delete' => 'Kullanıcı Silme',

            'roles.view' => 'Rolleri Görüntüleme',
            'roles.create' => 'Rol Oluşturma',
            'roles.update' => 'Rol Güncelleme',
            'roles.delete' => 'Rol Silme',

            'permissions.view' => 'Yetkileri Görüntüleme',
            'permissions.create' => 'Yetki Oluşturma',
            'permissions.update' => 'Yetki Güncelleme',
            'permissions.delete' => 'Yetki Silme',

            // Projects
            'projects.view' => 'Projeleri Görüntüleme',
            'projects.create' => 'Proje Oluşturma',
            'projects.update' => 'Proje Güncelleme',
            'projects.delete' => 'Proje Silme',
            'projects.trash' => 'Proje Çöp Kutusu',
            'projects.restore' => 'Proje Geri Yükleme',
            'projects.force_delete' => 'Proje Kalıcı Silme',
            'projects.state_change' => 'Proje Durumu Değiştirme',
        ];

        // Optional: extra permissions per module.
        // Supported formats (each module file should return array):
        // 1) ['blog.view', 'blog.create', ...]  -> name auto-generated
        // 2) ['blog.view' => 'Yazıları Görüntüleme', ...]  -> explicit names
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

        Rbac::bumpVersion();

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
