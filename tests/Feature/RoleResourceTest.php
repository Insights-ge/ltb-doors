<?php

use App\Enums\Role as RoleEnum;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\RoleResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();

    $this->superAdmin = User::where('email', 'demo@demo.com')->first();
    $this->admin = User::where('email', 'admin@ltb.ge')->first();
});

test('role names are translated in the roles table', function () {
    app()->setLocale('ka');

    $this->actingAs($this->superAdmin);

    Livewire::test(ListRoles::class)
        ->assertSee(__('panel.roles.super_admin'))
        ->assertSee(__('panel.roles.admin'))
        ->assertDontSee('super_admin');
});

// Super admin visibility

test('admin cannot see the super admin role in the list', function () {
    $this->actingAs($this->admin);

    $superAdminRole = Role::where('name', RoleEnum::SuperAdmin->value)->first();
    $adminRole = Role::where('name', RoleEnum::Admin->value)->first();

    Livewire::test(ListRoles::class)
        ->assertCanSeeTableRecords([$adminRole])
        ->assertCanNotSeeTableRecords([$superAdminRole]);
});

test('super admin can see the super admin role in the list', function () {
    $this->actingAs($this->superAdmin);

    $superAdminRole = Role::where('name', RoleEnum::SuperAdmin->value)->first();

    Livewire::test(ListRoles::class)
        ->assertCanSeeTableRecords([$superAdminRole]);
});

test('admin cannot access the edit page of the super admin role', function () {
    $this->actingAs($this->admin);

    $superAdminRole = Role::where('name', RoleEnum::SuperAdmin->value)->first();

    // The super admin role is excluded from the admin's scoped query
    // entirely, so route model binding can't resolve it: this 404s.
    $this->get(RoleResource::getUrl('edit', ['record' => $superAdminRole]))
        ->assertNotFound();
});

test('super admin can access the edit page of the super admin role', function () {
    $this->actingAs($this->superAdmin);

    $superAdminRole = Role::where('name', RoleEnum::SuperAdmin->value)->first();

    Livewire::test(EditRole::class, ['record' => $superAdminRole->getKey()])
        ->assertSuccessful();
});
