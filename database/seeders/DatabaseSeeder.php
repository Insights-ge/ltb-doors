<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'demo@demo.com'],
            [
                'name' => 'Insights Demo Admin',
                'password' => Hash::make('demo'),
                'email_verified_at' => now(),
            ]
        );

        $this->command->call('shield:generate', [
            '--all' => true,
            '--panel' => 'admin',
            '--option' => 'policies_and_permissions',
        ]);

        $this->command->call('shield:super-admin', [
            '--user' => 1,
            '--panel' => 'admin',
        ]);

        Role::firstOrCreate(['name' => RoleEnum::Admin->value]);

        $this->call(GeneralSettingsSeeder::class);
        $this->call(BackupPermissionSeeder::class);
    }
}
