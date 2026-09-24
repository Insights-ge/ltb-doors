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
        $this->call(UserSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(GeneralSettingsSeeder::class);
        $this->call(BackupPermissionSeeder::class);

        // Not part of the default chain: it's a ~450-row catalog import, not
        // app bootstrap data, and every test calling $this->seed() would pay
        // for it. Run explicitly: php artisan db:seed --class=DoorCatalogSeeder
        // $this->call(DoorCatalogSeeder::class);
    }
}
