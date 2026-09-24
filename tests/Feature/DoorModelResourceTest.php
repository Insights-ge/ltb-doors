<?php

use App\Filament\Resources\DoorModels\Pages\CreateDoorModel;
use App\Filament\Resources\DoorModels\Pages\EditDoorModel;
use App\Filament\Resources\DoorModels\Pages\ListDoorModels;
use App\Models\DoorModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();

    $this->actingAs(User::where('email', 'demo@demo.com')->first());
});

test('door models list page shows records', function () {
    $doorModel = DoorModel::factory()->create();

    Livewire::test(ListDoorModels::class)
        ->assertCanSeeTableRecords([$doorModel]);
});

test('a door model can be created', function () {
    Livewire::test(CreateDoorModel::class)
        ->fillForm([
            'name' => 'ტესტი',
            'min_height' => 800,
            'max_height' => 2600,
            'min_width' => 500,
            'max_width' => 1100,
            'not_recommended_height_min' => 2601,
            'not_recommended_height_max' => 2700,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('door_models', ['name' => 'ტესტი']);
});

test('creating a door model requires a name', function () {
    Livewire::test(CreateDoorModel::class)
        ->fillForm(['name' => ''])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});

test('a door model can be edited', function () {
    $doorModel = DoorModel::factory()->create(['name' => 'ძველი']);

    Livewire::test(EditDoorModel::class, ['record' => $doorModel->getKey()])
        ->fillForm(['name' => 'ახალი'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($doorModel->refresh()->name)->toBe('ახალი');
});
