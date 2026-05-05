<?php

namespace Database\Seeders;

use App\Models\Admin\User\Permission;
use App\Support\Rbac;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Yetki anahtarı => kullanıcıya görünen ad
        $permissions = [
            // Panel
            'admin.access' => 'Panel Erişimi',

            // Kullanıcılar / Roller / Yetkiler
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

            // Projeler
            'projects.view' => 'Projeleri Görüntüleme',
            'projects.create' => 'Proje Oluşturma',
            'projects.update' => 'Proje Güncelleme',
            'projects.delete' => 'Proje Silme',
            'projects.trash' => 'Proje Çöp Kutusu',
            'projects.restore' => 'Proje Geri Yükleme',
            'projects.force_delete' => 'Proje Kalıcı Silme',
            'projects.state_change' => 'Proje Durumu Değiştirme',
        ];

        // İsteğe bağlı: modül bazlı ek yetkiler.
        // Desteklenen biçimler (her modül dosyası array döndürür):
        // 1) ['blog.view', 'blog.create', ...]  -> ad otomatik üretilir
        // 2) ['blog.view' => 'Yazıları Görüntüleme', ...]  -> açık adlar kullanılır
        $modulesDir = database_path('seeders/permissions/modules');
        if (is_dir($modulesDir)) {
            foreach (glob($modulesDir . '/*.php') as $f) {
                $extra = require $f;

                if (!is_array($extra)) {
                    continue;
                }

                foreach ($extra as $k => $v) {
                    if (is_int($k)) {
                        // Yetki anahtarı listesi
                        $slug = (string) $v;
                        $permissions[$slug] = $permissions[$slug] ?? $this->defaultNameFromSlug($slug);
                    } else {
                        // Yetki anahtarı => ad
                        $permissions[(string) $k] = (string) $v;
                    }
                }
            }
        }

        // Tekrarlanabilir güncelleme
        foreach ($permissions as $slug => $name) {
            Permission::updateOrCreate(
                ['slug' => $slug],
                ['name' => $name]
            );
        }

        Rbac::bumpVersion();

        $this->command?->info('PermissionSeeder: yetkiler güncellendi (' . count($permissions) . ').');
    }

    private function defaultNameFromSlug(string $slug): string
    {
        $slug = trim($slug);
        if ($slug === '') return 'Yetki';

        $parts = preg_split('/[._-]+/', $slug) ?: [$slug];
        $parts = array_map(fn ($p) => ucfirst(strtolower($p)), $parts);

        return implode(' ', $parts);
    }
}
