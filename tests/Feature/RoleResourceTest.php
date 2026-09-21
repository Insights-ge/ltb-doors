<?php

use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();

    $this->superAdmin = User::where('email', 'demo@demo.com')->first();
});

test('role names are translated in the roles table', function () {
    app()->setLocale('ka');

    $this->actingAs($this->superAdmin);

    Livewire::test(ListRoles::class)
        ->assertSee(__('panel.roles.super_admin'))
        ->assertSee(__('panel.roles.admin'))
        ->assertDontSee('super_admin');
});
