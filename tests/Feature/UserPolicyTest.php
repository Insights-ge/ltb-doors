<?php

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();

    $this->superAdmin = User::where('email', 'demo@demo.com')->first();
    $this->admin = User::where('email', 'admin@ltb.ge')->first();
});

test('a super admin can update and delete another super admin', function () {
    $otherSuperAdmin = User::factory()->create();
    $otherSuperAdmin->assignRole(RoleEnum::SuperAdmin->value);

    expect($this->superAdmin->can('update', $otherSuperAdmin))->toBeTrue()
        ->and($this->superAdmin->can('delete', $otherSuperAdmin))->toBeTrue();
});

test('a super admin can update and delete an admin', function () {
    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole(RoleEnum::Admin->value);

    expect($this->superAdmin->can('update', $otherAdmin))->toBeTrue()
        ->and($this->superAdmin->can('delete', $otherAdmin))->toBeTrue();
});

test('a super admin can update and delete themselves', function () {
    expect($this->superAdmin->can('update', $this->superAdmin))->toBeTrue()
        ->and($this->superAdmin->can('delete', $this->superAdmin))->toBeTrue();
});

test('an admin can update and delete another admin', function () {
    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole(RoleEnum::Admin->value);

    expect($this->admin->can('update', $otherAdmin))->toBeTrue()
        ->and($this->admin->can('delete', $otherAdmin))->toBeTrue();
});

test('an admin cannot update or delete a super admin', function () {
    expect($this->admin->can('update', $this->superAdmin))->toBeFalse()
        ->and($this->admin->can('delete', $this->superAdmin))->toBeFalse();
});

test('an admin cannot update or delete themselves', function () {
    expect($this->admin->can('update', $this->admin))->toBeFalse()
        ->and($this->admin->can('delete', $this->admin))->toBeFalse();
});
