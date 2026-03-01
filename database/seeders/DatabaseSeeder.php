<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Order matters: permissions first, then roles/users.
        $this->call([
            PermissionSeeder::class,
            SuperAdminSeeder::class,
            AdminSeeder::class,
        ]);
    }
}
