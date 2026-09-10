<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->call('shield:generate', [
            '--all' => true,
            '--panel' => 'admin',
            '--option' => 'policies_and_permissions',
            '--ignore-existing-policies' => true,
        ]);

        $this->command->call('shield:super-admin', [
            '--user' => 1,
            '--panel' => 'admin',
        ]);

        $adminRole = Role::firstOrCreate(['name' => RoleEnum::Admin->value]);
        $adminRole->givePermissionTo(['ViewAny:User', 'View:User']);

        User::query()
            ->where('email', 'admin@ltb.ge')
            ->first()
            ?->assignRole(RoleEnum::Admin->value);
    }
}
