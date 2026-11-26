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
        $this->call(RoleSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(AdminPermissionSeeder::class);
        $this->call(SuperAdminPermissionSeeder::class);
        $this->call(GeneralSettingsSeeder::class);
        $this->call(DoorCatalogSeeder::class);
        $this->call(DoorLayoutSeeder::class);
        $this->call(InfillSeeder::class);
        $this->call(InfillIncompatibilitySeeder::class);
    }
}
