<?php

namespace Database\Seeders;

use App\Models\Admin\User\Permission;
use App\Models\Admin\User\Role;
use App\Support\Rbac;
use Illuminate\Database\Seeder;

class ServiceReviewPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            'service_reviews.view' => 'Hizmet Değerlendirmelerini Görüntüleme',
            'service_reviews.questions' => 'Değerlendirme Sorularını Yönetme',
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
        $this->command?->info('ServiceReviewPermissionSeeder: değerlendirme yetkileri eklendi.');
    }
}
