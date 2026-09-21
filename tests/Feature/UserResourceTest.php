<?php

use App\Enums\Role as RoleEnum;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
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

// Listing

test('admin cannot see super admin users in the list', function () {
    $this->actingAs($this->admin);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$this->admin])
        ->assertCanNotSeeTableRecords([$this->superAdmin]);
});

test('super admin can see all users, including other super admins', function () {
    $this->actingAs($this->superAdmin);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$this->admin, $this->superAdmin]);
});

// Creating

test('admin can create a user', function () {
    $adminRoleId = Role::where('name', RoleEnum::Admin->value)->value('id');

    $this->actingAs($this->admin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password',
            'roles' => [$adminRoleId],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
});

test('creating a user requires a name and an email', function () {
    $this->actingAs($this->admin);

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => '', 'email' => ''])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required', 'email' => 'required']);
});

test('admin cannot assign the super admin role when creating a user', function () {
    $superAdminRoleId = Role::where('name', RoleEnum::SuperAdmin->value)->value('id');

    $this->actingAs($this->admin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Sneaky User',
            'email' => 'sneaky@example.com',
            'password' => 'password',
            'roles' => [$superAdminRoleId],
        ])
        ->call('create')
        ->assertHasFormErrors(['roles']);

    $this->assertDatabaseMissing('users', ['email' => 'sneaky@example.com']);
});

test('super admin can create a user with the super admin role', function () {
    $superAdminRoleId = Role::where('name', RoleEnum::SuperAdmin->value)->value('id');

    $this->actingAs($this->superAdmin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'New Super Admin',
            'email' => 'newsuperadmin@example.com',
            'password' => 'password',
            'roles' => [$superAdminRoleId],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $newSuperAdmin = User::where('email', 'newsuperadmin@example.com')->first();

    expect($newSuperAdmin)->not->toBeNull()
        ->and($newSuperAdmin->hasRole(RoleEnum::SuperAdmin->value))->toBeTrue();
});

// Editing

test('admin can edit another admin', function () {
    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole(RoleEnum::Admin->value);

    $this->actingAs($this->admin);

    Livewire::test(EditUser::class, ['record' => $otherAdmin->getKey()])
        ->fillForm(['name' => 'Updated Name'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($otherAdmin->refresh()->name)->toBe('Updated Name');
});

test('admin cannot access the edit page of a super admin', function () {
    $this->actingAs($this->admin);

    // The super admin is excluded from the admin's scoped query entirely, so
    // route model binding can't resolve them: this 404s rather than 403s.
    $this->get(UserResource::getUrl('edit', ['record' => $this->superAdmin]))
        ->assertNotFound();
});

test('admin cannot access their own edit page', function () {
    $this->actingAs($this->admin);

    $this->get(UserResource::getUrl('edit', ['record' => $this->admin]))
        ->assertForbidden();
});

test('super admin can edit their own account', function () {
    $this->actingAs($this->superAdmin);

    Livewire::test(EditUser::class, ['record' => $this->superAdmin->getKey()])
        ->fillForm(['name' => 'Updated Super Admin'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->superAdmin->refresh()->name)->toBe('Updated Super Admin');
});

test('admin cannot assign the super admin role when editing a user', function () {
    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole(RoleEnum::Admin->value);
    $superAdminRoleId = Role::where('name', RoleEnum::SuperAdmin->value)->value('id');

    $this->actingAs($this->admin);

    Livewire::test(EditUser::class, ['record' => $otherAdmin->getKey()])
        ->fillForm(['roles' => [$superAdminRoleId]])
        ->call('save')
        ->assertHasFormErrors(['roles']);
});

// Deleting

test('admin can delete another admin', function () {
    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole(RoleEnum::Admin->value);

    $this->actingAs($this->admin);

    Livewire::test(ListUsers::class)
        ->callTableAction('delete', $otherAdmin);

    $this->assertModelMissing($otherAdmin);
});

test('edit and delete actions are hidden for an admin viewing their own row', function () {
    $this->actingAs($this->admin);

    Livewire::test(ListUsers::class)
        ->assertTableActionHidden('edit', $this->admin)
        ->assertTableActionHidden('delete', $this->admin);
});

test('edit and delete actions are visible for an admin viewing another admin\'s row', function () {
    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole(RoleEnum::Admin->value);

    $this->actingAs($this->admin);

    Livewire::test(ListUsers::class)
        ->assertTableActionVisible('edit', $otherAdmin)
        ->assertTableActionVisible('delete', $otherAdmin);
});
