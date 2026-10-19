<?php

use App\Enums\Role as RoleEnum;
use App\Models\DoorVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('super admins and admins can import door variants', function (string $email) {
    expect(User::where('email', $email)->first()->can('import', DoorVariant::class))->toBeTrue();
})->with(['demo@demo.com', 'admin@ltb.ge']);

test('users cannot import door variants', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::User->value);

    expect($user->can('import', DoorVariant::class))->toBeFalse();
});
