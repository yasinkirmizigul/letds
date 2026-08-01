<?php

namespace Database\Seeders;

use App\Models\Admin\User\Permission;
use App\Models\Admin\User\Role;
use App\Support\Rbac;
use Illuminate\Database\Seeder;

class HomepageConfigPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            'site_homepage.view' => 'Ana Sayfa Ayarlarını Görüntüleme',
            'site_homepage.update' => 'Ana Sayfa Ayarlarını Güncelleme',
        ];

        $permissionIds = collect($definitions)
            ->map(function (string $name, string $slug): int {
                return (int) Permission::query()->updateOrCreate(
                    ['slug' => $slug],
                    ['name' => $name]
                )->id;
            })
            ->values()
            ->all();

        Role::query()
            ->whereIn('slug', ['admin', 'superadmin'])
            ->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching($permissionIds));

        Rbac::bumpVersion();
        $this->command?->info('HomepageConfigPermissionSeeder: ana sayfa yetkileri eklendi.');
    }
}
