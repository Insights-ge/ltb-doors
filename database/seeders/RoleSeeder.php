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

        $superAdmin = User::query()->where('email', 'demo@demo.com')->first();

        $this->command->call('shield:super-admin', [
            '--user' => $superAdmin->id,
            '--panel' => 'admin',
        ]);

        $adminRole = Role::firstOrCreate(['name' => RoleEnum::Admin->value]);
        $adminRole->givePermissionTo([
            'ViewAny:User',
            'View:User',
            'Create:User',
            'Update:User',
            'Delete:User',
            'ViewAny:Role',
            'View:Role',
            'Create:Role',
            'Update:Role',
            'Delete:Role',
            'ViewAny:DoorModel',
            'View:DoorModel',
            'Create:DoorModel',
            'Update:DoorModel',
            'Delete:DoorModel',
            'ViewAny:Component',
            'View:Component',
            'Create:Component',
            'Update:Component',
            'Delete:Component',
            'ViewAny:DoorVariant',
            'View:DoorVariant',
            'Create:DoorVariant',
            'Update:DoorVariant',
            'Delete:DoorVariant',
        ]);

        User::query()
            ->where('email', 'admin@ltb.ge')
            ->first()
            ?->assignRole(RoleEnum::Admin->value);

        Role::firstOrCreate(['name' => RoleEnum::User->value]);
    }
}
